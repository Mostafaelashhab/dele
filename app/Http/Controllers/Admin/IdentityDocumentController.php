<?php

namespace App\Http\Controllers\Admin;

use App\Domain\Audit\AuditLogger;
use App\Enums\AuditAction;
use App\Http\Controllers\Controller;
use App\Models\Rider;
use Illuminate\Http\Request;
use Illuminate\Http\Response;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

/**
 * Serves a rider's identity document to the person reviewing it.
 *
 * These files live on the private disk and have no URL of their own — that is
 * the whole reason they are acceptable to hold. This is the only way to see
 * one, and it exists so that a reviewer can do the job the rider was promised
 * would be done.
 *
 * Three things make that safe. The route sits behind platform-staff
 * middleware, so nobody outside the platform reaches it. Only the two ID
 * columns can be requested, so the parameter cannot be turned into a reader
 * for arbitrary paths. And every view is written to the audit log: taking
 * somebody's ID card obliges you to be able to say who looked at it.
 */
class IdentityDocumentController extends Controller
{
    /**
     * The only attributes this endpoint will serve.
     *
     * An allowlist rather than validation: a rider has other columns holding
     * paths, and a reviewer has no business reading them through here.
     */
    private const DOCUMENTS = ['id_card_front_path', 'id_card_back_path'];

    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {}

    public function __invoke(Request $request, Rider $rider, string $document): Response
    {
        if (! in_array($document, self::DOCUMENTS, true)) {
            throw new NotFoundHttpException;
        }

        $contents = $rider->privateMediaContents($document);

        if ($contents === null) {
            throw new NotFoundHttpException;
        }

        $this->auditLogger->log(
            action: AuditAction::IdentityViewed,
            entity: $rider,
            description: 'Identity document viewed.',
            context: ['document' => $document],
            tenantType: 'delivery_company',
            tenantId: $rider->delivery_company_id,
        );

        return response($contents, 200, [
            'Content-Type' => $this->mimeFor($rider->getAttribute($document)),
            // Never cached and never stored: a reviewer's browser should not
            // keep somebody's national ID in its disk cache.
            'Cache-Control' => 'no-store, private, max-age=0',
            'Content-Disposition' => 'inline',
            'X-Content-Type-Options' => 'nosniff',
        ]);
    }

    private function mimeFor(string $path): string
    {
        return match (mb_strtolower(pathinfo($path, PATHINFO_EXTENSION))) {
            'png' => 'image/png',
            'webp' => 'image/webp',
            'heic', 'heif' => 'image/heic',
            default => 'image/jpeg',
        };
    }
}
