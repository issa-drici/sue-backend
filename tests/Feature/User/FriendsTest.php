<?php

namespace Tests\Feature\User;

use App\Models\FriendRequestModel;
use App\Models\NotificationModel;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Str;
use Tests\Concerns\SueTestHelpers;
use Tests\TestCase;

/**
 * Amis : demandes (envoi, réponse, annulation, compteur, liste) et suppression d'amis.
 */
class FriendsTest extends TestCase
{
    use RefreshDatabase, SueTestHelpers;

    // --- Authentification ---------------------------------------------------------------

    public function test_toutes_les_routes_amis_exigent_une_authentification(): void
    {
        $id = (string) Str::uuid();
        $this->getJson('/api/users/friends')->assertUnauthorized();
        $this->deleteJson("/api/users/friends/{$id}")->assertUnauthorized();
        $this->getJson('/api/users/friend-requests')->assertUnauthorized();
        $this->getJson('/api/users/friend-requests/count')->assertUnauthorized();
        $this->postJson('/api/users/friend-requests', ['userId' => $id])->assertUnauthorized();
        $this->patchJson("/api/users/friend-requests/{$id}", ['response' => 'accept'])->assertUnauthorized();
        $this->deleteJson('/api/users/friend-requests', ['target_user_id' => $id])->assertUnauthorized();
    }

    // --- Envoi ----------------------------------------------------------------------------

    public function test_envoyer_une_demande_cree_la_demande_notifie_et_pousse(): void
    {
        $alice = $this->makeUser('Alice');
        $bob = $this->makeUser('Bob');

        $this->actingAs($alice)->postJson('/api/users/friend-requests', ['userId' => $bob->id])
            ->assertCreated()
            ->assertJson(['success' => true, 'data' => ['id' => $bob->id, 'firstname' => 'Bob', 'mutualFriends' => 0]]);

        $this->assertDatabaseHas('friend_requests', ['sender_id' => $alice->id, 'receiver_id' => $bob->id, 'status' => 'pending']);
        $this->assertDatabaseHas('notifications', ['user_id' => $bob->id, 'type' => 'friend_request']);
        $this->assertCount(1, $this->pushesFor($bob));
        $this->assertSame('friend_request', $this->pushesFor($bob)[0]['data']['type']);
        $this->assertStringContainsString('Alice', $this->pushesFor($bob)[0]['body']);
        $this->assertSame([], $this->pushesFor($alice));
    }

    public function test_envoyer_une_demande_compte_les_amis_en_commun(): void
    {
        [$alice, $bob, $carol] = [$this->makeUser('Alice'), $this->makeUser('Bob'), $this->makeUser('Carol')];
        $this->befriend($alice, $carol);
        $this->befriend($bob, $carol);

        $this->actingAs($alice)->postJson('/api/users/friend-requests', ['userId' => $bob->id])
            ->assertCreated()->assertJsonPath('data.mutualFriends', 1);
    }

    public function test_envoyer_une_demande_sans_user_id_est_refuse(): void
    {
        $this->actingAs($this->makeUser('Alice'))->postJson('/api/users/friend-requests', [])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_envoyer_une_demande_a_un_inconnu_renvoie_404(): void
    {
        $this->actingAs($this->makeUser('Alice'))->postJson('/api/users/friend-requests', ['userId' => (string) Str::uuid()])
            ->assertNotFound()->assertJsonPath('error.code', 'USER_NOT_FOUND');
    }

    public function test_on_ne_peut_pas_s_envoyer_une_demande_a_soi_meme(): void
    {
        $alice = $this->makeUser('Alice');
        $this->actingAs($alice)->postJson('/api/users/friend-requests', ['userId' => $alice->id])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->assertDatabaseCount('friend_requests', 0);
    }

    public function test_on_ne_peut_pas_demander_un_ami_existant(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $this->befriend($alice, $bob);

        $this->actingAs($alice)->postJson('/api/users/friend-requests', ['userId' => $bob->id])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
    }

    public function test_une_demande_en_attente_ne_peut_pas_etre_dupliquee_dans_les_deux_sens(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $this->friendRequest($alice, $bob);

        $this->actingAs($alice)->postJson('/api/users/friend-requests', ['userId' => $bob->id])
            ->assertStatus(409)->assertJsonPath('error.code', 'FRIEND_REQUEST_EXISTS');
        $this->actingAs($bob)->postJson('/api/users/friend-requests', ['userId' => $alice->id])
            ->assertStatus(409);
        $this->assertDatabaseCount('friend_requests', 1);
    }

    public function test_une_demande_refusee_peut_etre_renvoyee(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $request = $this->friendRequest($alice, $bob, 'declined');

        // Bob relance la demande dans l'autre sens : la ligne existante est réutilisée
        $this->actingAs($bob)->postJson('/api/users/friend-requests', ['userId' => $alice->id])->assertCreated();

        $this->assertDatabaseCount('friend_requests', 1);
        $this->assertDatabaseHas('friend_requests', [
            'id' => $request->id, 'sender_id' => $bob->id, 'receiver_id' => $alice->id, 'status' => 'pending',
        ]);
    }

    // --- Liste + compteur ----------------------------------------------------------------

    public function test_liste_et_compteur_des_demandes_recues_en_attente(): void
    {
        [$me, $a, $b, $c, $d] = array_map(fn ($n) => $this->makeUser($n), ['Me', 'Ann', 'Ben', 'Cid', 'Dan']);
        $this->friendRequest($a, $me);
        $this->friendRequest($b, $me);
        $this->friendRequest($c, $me, 'declined');
        $this->friendRequest($me, $d); // envoyée, pas reçue

        $this->actingAs($me)->getJson('/api/users/friend-requests')
            ->assertOk()
            ->assertJsonCount(2, 'data')
            ->assertJsonPath('pagination.total', 2)
            ->assertJsonStructure(['data' => [['id', 'sender' => ['id', 'firstname', 'lastname', 'avatar'], 'status', 'created_at']]]);

        $this->actingAs($me)->getJson('/api/users/friend-requests/count')
            ->assertOk()->assertJson(['success' => true, 'data' => ['count' => 2]]);

        $this->actingAs($me)->getJson('/api/users/friend-requests?limit=1&page=2')
            ->assertOk()->assertJsonCount(1, 'data')->assertJsonPath('pagination.totalPages', 2);
    }

    public function test_le_compteur_ignore_les_demandes_annulees(): void
    {
        [$me, $a] = [$this->makeUser('Me'), $this->makeUser('Ann')];
        $request = $this->friendRequest($a, $me);
        $request->update(['cancelled_at' => now()]);

        $this->actingAs($me)->getJson('/api/users/friend-requests/count')->assertJsonPath('data.count', 0);
    }

    public function test_liste_des_amis_paginee(): void
    {
        [$me, $a, $b, $stranger] = array_map(fn ($n) => $this->makeUser($n), ['Me', 'Ann', 'Ben', 'Zed']);
        $this->befriend($me, $a);
        $this->befriend($me, $b);
        $this->befriend($a, $stranger);

        $response = $this->actingAs($me)->getJson('/api/users/friends')->assertOk()->assertJsonPath('pagination.total', 2);
        $ids = collect($response->json('data'))->pluck('id')->all();
        $this->assertEqualsCanonicalizing([$a->id, $b->id], $ids);

        $this->actingAs($me)->getJson('/api/users/friends?limit=1')->assertJsonCount(1, 'data')->assertJsonPath('pagination.totalPages', 2);
    }

    // --- Réponse --------------------------------------------------------------------------

    public function test_accepter_une_demande_cree_l_amitie_dans_les_deux_sens(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $request = $this->friendRequest($alice, $bob);

        $this->actingAs($bob)->patchJson("/api/users/friend-requests/{$request->id}", ['response' => 'accept'])
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['id' => $alice->id, 'firstname' => 'Alice', 'status' => 'accepted']]);

        $this->assertTrue($this->areFriends($alice, $bob));
        $this->assertSame('accepted', $request->fresh()->status);
    }

    public function test_refuser_une_demande_ne_cree_pas_d_amitie(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $request = $this->friendRequest($alice, $bob);

        $this->actingAs($bob)->patchJson("/api/users/friend-requests/{$request->id}", ['response' => 'decline'])
            ->assertOk()->assertJsonPath('data.status', 'declined');

        $this->assertFalse($this->areFriends($alice, $bob));
        $this->assertSame('declined', $request->fresh()->status);
    }

    public function test_reponse_invalide_ou_demande_inconnue(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $request = $this->friendRequest($alice, $bob);

        $this->actingAs($bob)->patchJson("/api/users/friend-requests/{$request->id}", ['response' => 'maybe'])
            ->assertStatus(400)->assertJsonPath('error.code', 'VALIDATION_ERROR');
        $this->actingAs($bob)->patchJson('/api/users/friend-requests/' . Str::uuid(), ['response' => 'accept'])
            ->assertNotFound();
    }

    public function test_seul_le_destinataire_peut_repondre_a_une_demande(): void
    {
        [$alice, $bob, $mallory] = [$this->makeUser('Alice'), $this->makeUser('Bob'), $this->makeUser('Mallory')];
        $request = $this->friendRequest($alice, $bob);

        $this->actingAs($mallory)->patchJson("/api/users/friend-requests/{$request->id}", ['response' => 'accept'])
            ->assertForbidden();
        $this->assertFalse($this->areFriends($alice, $mallory));
        $this->assertSame('pending', $request->fresh()->status);

        // L'expéditeur ne peut pas accepter sa propre demande
        $this->actingAs($alice)->patchJson("/api/users/friend-requests/{$request->id}", ['response' => 'accept'])
            ->assertForbidden();
        $this->assertFalse($this->areFriends($alice, $bob));
    }

    public function test_on_ne_peut_pas_repondre_a_une_demande_deja_traitee(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $request = $this->friendRequest($alice, $bob, 'declined');

        $this->actingAs($bob)->patchJson("/api/users/friend-requests/{$request->id}", ['response' => 'accept'])
            ->assertStatus(409)
            ->assertJsonPath('error.code', 'ALREADY_HANDLED')
            ->assertJsonPath('error.message', "Cette demande d'ami a déjà été traitée.");
        $this->assertFalse($this->areFriends($alice, $bob));
        $this->assertSame('declined', $request->fresh()->status);
    }

    // --- Annulation -----------------------------------------------------------------------

    public function test_annuler_sa_demande_envoyee(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $request = $this->friendRequest($alice, $bob);

        $this->actingAs($alice)->deleteJson('/api/users/friend-requests', ['target_user_id' => $bob->id])
            ->assertOk()
            ->assertJson(['success' => true, 'data' => ['requestId' => $request->id, 'status' => 'cancelled']]);

        $fresh = $request->fresh();
        $this->assertSame('cancelled', $fresh->status);
        $this->assertNotNull($fresh->cancelled_at);
    }

    public function test_annulation_erreurs(): void
    {
        [$alice, $bob, $carol] = [$this->makeUser('Alice'), $this->makeUser('Bob'), $this->makeUser('Carol')];
        $this->friendRequest($alice, $bob, 'accepted');

        $this->actingAs($alice)->deleteJson('/api/users/friend-requests', [])
            ->assertStatus(400)->assertJsonPath('error.code', 'MISSING_TARGET_USER_ID');
        $this->actingAs($alice)->deleteJson('/api/users/friend-requests', ['target_user_id' => 'pas-un-uuid'])
            ->assertStatus(400)->assertJsonPath('error.code', 'INVALID_USER_ID');
        $this->actingAs($alice)->deleteJson('/api/users/friend-requests', ['target_user_id' => $carol->id])
            ->assertNotFound()->assertJsonPath('error.code', 'FRIEND_REQUEST_NOT_FOUND');
        // Le destinataire ne peut pas « annuler » : il n'est pas l'expéditeur, la demande n'est pas trouvée
        $this->actingAs($bob)->deleteJson('/api/users/friend-requests', ['target_user_id' => $alice->id])
            ->assertNotFound();
        // Déjà acceptée
        $this->actingAs($alice)->deleteJson('/api/users/friend-requests', ['target_user_id' => $bob->id])
            ->assertStatus(409)->assertJsonPath('error.code', 'REQUEST_ALREADY_PROCESSED');
    }

    // --- Suppression d'ami ----------------------------------------------------------------

    public function test_supprimer_un_ami_dans_les_deux_sens_et_permettre_une_nouvelle_demande(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];
        $this->befriend($alice, $bob);
        $request = $this->friendRequest($alice, $bob, 'accepted');

        $this->actingAs($bob)->deleteJson("/api/users/friends/{$alice->id}")
            ->assertOk()->assertJson(['success' => true, 'data' => ['removedFriendId' => $alice->id]]);

        $this->assertDatabaseCount('friends', 0);
        $this->assertSame('cancelled', $request->fresh()->status);

        // Nouveau cycle possible
        $this->actingAs($alice)->postJson('/api/users/friend-requests', ['userId' => $bob->id])->assertCreated();
        $this->assertSame('pending', FriendRequestModel::first()->status);
    }

    public function test_suppression_d_ami_erreurs(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob')];

        $this->actingAs($alice)->deleteJson('/api/users/friends/pas-un-uuid')
            ->assertStatus(400)->assertJsonPath('error.code', 'INVALID_FRIEND_ID');
        $this->actingAs($alice)->deleteJson('/api/users/friends/' . Str::uuid())
            ->assertNotFound()->assertJsonPath('error.code', 'FRIEND_NOT_FOUND');
        $this->actingAs($alice)->deleteJson("/api/users/friends/{$alice->id}")
            ->assertStatus(400)->assertJsonPath('error.code', 'INVALID_FRIEND_ID');
        $this->actingAs($alice)->deleteJson("/api/users/friends/{$bob->id}")
            ->assertNotFound()->assertJsonPath('error.code', 'FRIEND_NOT_FOUND');
    }

    public function test_notification_in_app_de_demande_d_ami(): void
    {
        [$alice, $bob] = [$this->makeUser('Alice'), $this->makeUser('Bob', [], false)];
        $this->actingAs($alice)->postJson('/api/users/friend-requests', ['userId' => $bob->id])->assertCreated();

        // Sans jeton push : notification in-app quand même, aucun push
        $this->assertSame(1, NotificationModel::where('user_id', $bob->id)->count());
        $this->assertSame([], $this->pushes);
    }
}
