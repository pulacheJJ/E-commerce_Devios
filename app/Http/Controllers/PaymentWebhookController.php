<?php

namespace App\Http\Controllers;

use App\Services\Payments\WebhookPaymentService;
use DomainException;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Http\Client\RequestException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;
use InvalidArgumentException;

class PaymentWebhookController extends Controller
{
    public function __invoke(Request $request, string $provider, WebhookPaymentService $webhooks): JsonResponse
    {
        try {
            $headers = $request->headers->all();
            $dataId = $request->query('data_id')
                ?? $request->query->get('data.id')
                ?? $request->input('data.id');
            if (is_scalar($dataId)) {
                $headers['x-provider-data-id'] = [(string) $dataId];
            }

            $result = $webhooks->process($provider, $request->getContent(), $headers);

            return response()->json(['received' => true] + $result);
        } catch (InvalidArgumentException $exception) {
            Log::warning('Payment webhook could not be verified.', [
                'provider' => $provider,
                'reason' => $exception->getMessage(),
                'request_id' => $request->header('x-request-id'),
            ]);

            return response()->json(['message' => 'Webhook no verificable.'], 401);
        } catch (ModelNotFoundException) {
            return response()->json(['message' => 'Transacción desconocida.'], 404);
        } catch (DomainException $exception) {
            return response()->json(['message' => $exception->getMessage()], 409);
        } catch (RequestException $exception) {
            $status = $exception->response->status();

            if ($status === 404) {
                Log::info('Signed payment webhook references a resource unavailable in the provider API.', [
                    'provider' => $provider,
                    'request_id' => $request->header('x-request-id'),
                    'data_id' => $request->query('data_id') ?? $request->input('data.id'),
                ]);

                return response()->json([
                    'received' => true,
                    'ignored' => true,
                    'reason' => 'provider_resource_not_found',
                ]);
            }

            Log::error('Payment provider rejected webhook verification.', [
                'provider' => $provider,
                'request_id' => $request->header('x-request-id'),
                'status' => $status,
            ]);

            return response()->json(['message' => 'Proveedor temporalmente no disponible.'], 503);
        } catch (ConnectionException) {
            Log::error('Payment provider could not be reached while processing a webhook.', [
                'provider' => $provider,
                'request_id' => $request->header('x-request-id'),
            ]);

            return response()->json(['message' => 'Proveedor temporalmente no disponible.'], 503);
        }
    }
}
