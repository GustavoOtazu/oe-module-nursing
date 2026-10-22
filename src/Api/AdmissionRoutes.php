<?php

/**
 * Registers the Admission API in the OpenEMR standard REST API:
 *
 *   GET  /apis/default/api/nursing_admission?nro_registro=...      scope user/nursing_admission.s
 *   GET  /apis/default/api/nursing_admission?origen=sih&estado=abierta
 *   POST /apis/default/api/nursing_admission                         scope user/nursing_admission.c
 *   PUT  /apis/default/api/nursing_admission/:nro_registro           scope user/nursing_admission.u
 *
 * OpenEMR checks the OAuth2 access token and its scopes before calling the
 * route; the route also checks the user's ACL (patients/med), the same the
 * inpatient screens require.
 *
 * @package   OpenEMR
 * @link      https://www.open-emr.org
 * @author    Gustavo Otazu
 * @copyright Copyright (c) 2026 Gustavo Otazu
 * @license   https://github.com/openemr/openemr/blob/master/LICENSE GNU General Public License 3
 */

declare(strict_types=1);

namespace OpenEMR\Modules\Nursing\Api;

use OpenEMR\Common\Http\HttpRestRequest;
use OpenEMR\Events\RestApiExtend\RestApiCreateEvent;
use OpenEMR\Events\RestApiExtend\RestApiScopeEvent;
use OpenEMR\RestControllers\Config\RestConfig;
use Symfony\Component\HttpFoundation\JsonResponse;

final class AdmissionRoutes
{
    /** Scopes the module adds to the server: v1 (read/write) and v2 (c, r, u, s). */
    public const SCOPES = [
        'user/' . AdmissionApi::RESOURCE . '.read',
        'user/' . AdmissionApi::RESOURCE . '.write',
        'user/' . AdmissionApi::RESOURCE . '.crus',
    ];

    public static function addRoutes(RestApiCreateEvent $event): RestApiCreateEvent
    {
        $base = '/api/' . AdmissionApi::RESOURCE;

        $event->addToRouteMap('GET ' . $base, function (HttpRestRequest $request): JsonResponse {
            RestConfig::request_authorization_check($request, 'patients', 'med');
            return self::respond(AdmissionApi::fromDatabase()->search($request->query->all()));
        });

        $event->addToRouteMap('POST ' . $base, function (HttpRestRequest $request): JsonResponse {
            RestConfig::request_authorization_check($request, 'patients', 'med');
            $session = $request->getSession();
            return self::respond(AdmissionApi::fromDatabase()->create(
                self::body($request),
                (string) $session->get('authUser'),
                (string) $session->get('authProvider')
            ));
        });

        $event->addToRouteMap('PUT ' . $base . '/:nro_registro', function (string $nro, HttpRestRequest $request): JsonResponse {
            RestConfig::request_authorization_check($request, 'patients', 'med');
            return self::respond(AdmissionApi::fromDatabase()->update(
                $nro,
                self::body($request),
                (string) $request->getSession()->get('authUser')
            ));
        });

        return $event;
    }

    public static function addScopes(RestApiScopeEvent $event): RestApiScopeEvent
    {
        if ($event->getApiType() === RestApiScopeEvent::API_TYPE_STANDARD) {
            $event->setScopes(array_values(array_unique([...$event->getScopes(), ...self::SCOPES])));
        }
        return $event;
    }

    /** @return array<string, mixed> */
    private static function body(HttpRestRequest $request): array
    {
        $data = json_decode((string) $request->getContent(), true);
        return is_array($data) ? $data : [];
    }

    /** @param array{status: int, body: array<string, mixed>} $result */
    private static function respond(array $result): JsonResponse
    {
        return new JsonResponse($result['body'], $result['status']);
    }
}
