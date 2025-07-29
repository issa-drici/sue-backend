<?php

namespace App\UseCases\SportSession;

use App\Entities\SportSession;
use App\Repositories\SportSession\SportSessionRepositoryInterface;
use Exception;

class FindSportSessionByIdUseCase
{
    public function __construct(
        private SportSessionRepositoryInterface $sportSessionRepository
    ) {}

    public function execute(string $id): SportSession
    {
        $session = $this->sportSessionRepository->findById($id);

        if (!$session) {
            throw new Exception('Session non trouvée');
        }

        return $session;
    }
}
