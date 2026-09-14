<?php

namespace App\Exceptions;

/**
 * Odrzucenie paczki aktualizacji/migawki rollbacku z czytelnym powodem do
 * pokazania administratorowi — patrz UpdateService::validatePackage().
 */
class UpdatePackageException extends \RuntimeException
{
}
