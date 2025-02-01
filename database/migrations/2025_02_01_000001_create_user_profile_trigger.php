<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::unprepared('
            CREATE OR REPLACE FUNCTION create_user_profile()
            RETURNS TRIGGER AS $$
            BEGIN
                INSERT INTO user_profiles (
                    id,
                    user_id,
                    total_xp,
                    total_training_time,
                    completed_videos,
                    completed_days,
                    created_at,
                    updated_at
                )
                VALUES (
                    gen_random_uuid(),
                    NEW.id,
                    0,
                    0,
                    0,
                    0,
                    NOW(),
                    NOW()
                );
                RETURN NEW;
            END;
            $$ LANGUAGE plpgsql;

            CREATE TRIGGER create_user_profile_after_insert
            AFTER INSERT ON users
            FOR EACH ROW
            EXECUTE FUNCTION create_user_profile();
        ');
    }

    public function down(): void
    {
        DB::unprepared('
            DROP TRIGGER IF EXISTS create_user_profile_after_insert ON users;
            DROP FUNCTION IF EXISTS create_user_profile;
        ');
    }
}; 