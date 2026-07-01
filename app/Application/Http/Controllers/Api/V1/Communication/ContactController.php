<?php

namespace App\Application\Http\Controllers\Api\V1\Communication;

use App\Application\Http\Controllers\Controller;
use App\Domain\Communication\DTOs\SyncContactsDTO;
use App\Domain\Communication\Models\Contact;
use App\Domain\Device\Repositories\DeviceRepositoryInterface;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ContactController extends Controller
{
    public function __construct(
        protected DeviceRepositoryInterface $deviceRepo
    ) {}

    /**
     * POST /api/v1/devices/{uuid}/contacts/sync
     * مزامنة جهات الاتصال (من الجهاز)
     *
     * Body:
     * {
     *   "contacts": [
     *     { "name": "Ahmed", "phone_number": "+201234567890", "is_favorite": false }
     *   ]
     * }
     */
    public function sync(Request $request, string $uuid): JsonResponse
    {
        $request->validate([
            'contacts'                  => 'required|array|max:2000',
            'contacts.*.name'           => 'required|string|max:255',
            'contacts.*.phone_number'   => 'required|string|max:30',
            'contacts.*.phone_numbers'  => 'sometimes|array',
            'contacts.*.email'          => 'sometimes|nullable|email|max:255',
            'contacts.*.is_favorite'    => 'sometimes|boolean',
        ]);

        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $synced  = 0;
        $skipped = 0;

        foreach ($request->contacts as $contactData) {
            try {
                Contact::updateOrCreate(
                    [
                        'device_id'    => $device->id,
                        'phone_hash'   => Contact::hashPhone($contactData['phone_number']),
                    ],
                    [
                        'contact_name'  => $contactData['name'],
                        'phone_number'  => $contactData['phone_number'],
                        'phone_numbers' => $contactData['phone_numbers'] ?? null,
                        'email'         => $contactData['email']         ?? null,
                        'is_favorite'   => (bool) ($contactData['is_favorite'] ?? false),
                        'synced_at'     => now(),
                    ]
                );
                $synced++;
            } catch (\Exception) {
                $skipped++;
            }
        }

        return $this->success(
            ['synced' => $synced, 'skipped' => $skipped, 'total' => count($request->contacts)],
            "تم مزامنة {$synced} جهة اتصال."
        );
    }

    /**
     * GET /api/v1/devices/{uuid}/contacts
     * قائمة جهات الاتصال (للأب)
     *
     * Query: ?search=Ahmed  &favorites=1  &per_page=50
     */
    public function index(Request $request, string $uuid): JsonResponse
    {
        $device = $this->deviceRepo->findByUuid($uuid);

        if (!$device || $device->user_id !== $request->user()->id) {
            return $this->error('الجهاز غير موجود.', 404);
        }

        $query = Contact::where('device_id', $device->id)
            ->orderBy('id');

        if ($request->filled('search')) {
            $query->search($request->search);
        }

        if ($request->boolean('favorites')) {
            $query->favorites();
        }

        $contacts = $query->paginate($request->integer('per_page', 50));

        return $this->success([
            'contacts' => $contacts->map(fn (Contact $c) => [
                'id'             => $c->id,
                'name'           => $c->contact_name,
                'phone_number'   => $c->phone_number,
                'primary_number' => $c->primary_number,
                'email'          => $c->email,
                'is_favorite'    => $c->is_favorite,
                'synced_at'      => $c->synced_at?->toIso8601String(),
            ]),
            'pagination' => [
                'total'        => $contacts->total(),
                'per_page'     => $contacts->perPage(),
                'current_page' => $contacts->currentPage(),
                'last_page'    => $contacts->lastPage(),
            ],
        ]);
    }
}
