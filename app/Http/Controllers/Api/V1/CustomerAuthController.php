<?php

namespace App\Http\Controllers\Api\V1;

use App\Http\Requests\Api\V1\LoginRequest;
use App\Http\Requests\Api\V1\RegisterRequest;
use App\Http\Requests\Api\V1\UpdateProfileRequest;
use App\Http\Resources\CustomerResource;
use App\Models\Customer;
use App\Models\User;
use App\Notifications\CustomerProfileUpdatedNotification;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Notification;
use Illuminate\Support\Str;
use Spatie\Permission\Exceptions\RoleDoesNotExist;

class CustomerAuthController extends ApiController
{
    public function login(LoginRequest $request): JsonResponse
    {
        $identifier = trim((string) ($request->input('email')
            ?: $request->input('identifier')
            ?: $request->input('phone')));
        $password = (string) $request->input('password');
        $customer = $this->resolveCustomerByIdentifier($identifier);
        $isActive = $customer ? (bool) $customer->getAttribute('is_active') : false;

        if (! $customer || ! $isActive) {
            Log::warning('api.auth.login_failed', [
                'identifier' => $identifier,
                'reason' => 'customer_not_found_or_inactive',
                'ip' => $request->ip(),
            ]);

            return $this->error('Invalid credentials', 401);
        }

        if (! $this->matchesCustomerCredential($customer, $password)) {
            Log::warning('api.auth.login_failed', [
                'identifier' => $identifier,
                'customer_id' => $customer->id,
                'reason' => 'credential_mismatch',
                'ip' => $request->ip(),
            ]);

            return $this->error('Invalid credentials', 401);
        }

        $customer->tokens()->delete();
        $token = $customer->createToken((string) $request->input('device_name', 'mobile-device'))->plainTextToken;
        $this->storeCustomerToken($customer, $token);

        return $this->success([
            'token' => $token,
            'access_token' => $token,
            'customer' => new CustomerResource($customer),
        ]);
    }

    public function register(RegisterRequest $request): JsonResponse
    {
        $validated = $request->validated();

        $customer = Customer::query()->create([
            'name' => trim((string) $validated['name']),
            'email' => strtolower((string) $validated['email']),
            'phone' => trim((string) $validated['phone']),
            'whatsapp' => ! empty($validated['whatsapp']) ? trim((string) $validated['whatsapp']) : null,
            'city' => ! empty($validated['city']) ? trim((string) $validated['city']) : null,
            'address' => ! empty($validated['address']) ? trim((string) $validated['address']) : null,
            'password' => Hash::make((string) $validated['password']),
            'is_active' => false,
        ]);

        return $this->success([
            'customer' => new CustomerResource($customer),
        ], 201, 'Registration submitted. Awaiting activation.');
    }

    public function logout(Request $request): JsonResponse
    {
        /** @var \App\Models\Customer|null $customer */
        $customer = $request->user();
        $bearerToken = trim((string) $request->bearerToken());

        if ($customer && $bearerToken !== '' && is_string($customer->token) && $customer->token !== '') {
            if (hash_equals($customer->token, hash('sha256', $bearerToken))) {
                $customer->forceFill(['token' => null])->save();
            }
        }

        $customer?->currentAccessToken()?->delete();

        return $this->success(null, 200, 'Logged out successfully');
    }

    public function me(Request $request): JsonResponse
    {
        return $this->success(new CustomerResource($request->user()));
    }

    public function updateProfile(UpdateProfileRequest $request): JsonResponse
    {
        /** @var \App\Models\Customer $customer */
        $customer = $request->user();
        $oldProfile = $this->profileSnapshot($customer);

        $validated = $request->validated();
        $customer->fill([
            'name' => trim((string) $validated['name']),
            'email' => strtolower(trim((string) $validated['email'])),
            'phone' => trim((string) $validated['phone']),
            'whatsapp' => ! empty($validated['whatsapp']) ? trim((string) $validated['whatsapp']) : null,
            'city' => ! empty($validated['city']) ? trim((string) $validated['city']) : null,
            'address' => ! empty($validated['address']) ? trim((string) $validated['address']) : null,
        ]);

        $changedFields = array_keys($customer->getDirty());
        if ($changedFields === []) {
            return $this->success(new CustomerResource($customer), 200, 'No profile changes');
        }

        $customer->save();
        $customer->refresh();

        $newProfile = $this->profileSnapshot($customer);
        $this->notifyAdminsOfProfileUpdate($customer, $oldProfile, $newProfile, $changedFields);

        return $this->success(new CustomerResource($customer), 200, 'Profile updated');
    }

    private function resolveCustomerByIdentifier(string $identifier): ?Customer
    {
        if ($identifier === '') {
            return null;
        }

        $normalizedIdentifier = strtolower($identifier);

        if (filter_var($normalizedIdentifier, FILTER_VALIDATE_EMAIL)) {
            return Customer::query()
                ->whereRaw('LOWER(email) = ?', [$normalizedIdentifier])
                ->first();
        }

        $digits = $this->normalizePhoneDigits($identifier);
        if ($digits === '') {
            return null;
        }

        $direct = Customer::query()
            ->where('phone', $identifier)
            ->first();

        if ($direct) {
            return $direct;
        }

        $tail = Str::substr($digits, -4);
        if ($tail === '') {
            $tail = $digits;
        }

        $candidates = Customer::query()
            ->whereNotNull('phone')
            ->where('phone', 'like', '%'.$tail.'%')
            ->get();

        return $candidates->first(function (Customer $customer) use ($digits): bool {
            return $this->normalizePhoneDigits((string) $customer->phone) === $digits;
        });
    }

    private function normalizePhoneDigits(string $value): string
    {
        return preg_replace('/\D+/', '', $value) ?? '';
    }

    private function matchesCustomerCredential(Customer $customer, string $credential): bool
    {
        $hashedPassword = (string) ($customer->password ?? '');

        if ($hashedPassword === '') {
            return false;
        }

        return Hash::check($credential, $hashedPassword);
    }

    private function storeCustomerToken(Customer $customer, string $plainToken): void
    {
        $customer->forceFill([
            'token' => hash('sha256', $plainToken),
        ])->save();
    }

    /**
     * @return array<string, string>
     */
    private function profileSnapshot(Customer $customer): array
    {
        return [
            'name' => (string) ($customer->name ?? ''),
            'email' => (string) ($customer->email ?? ''),
            'phone' => (string) ($customer->phone ?? ''),
            'whatsapp' => (string) ($customer->whatsapp ?? ''),
            'city' => (string) ($customer->city ?? ''),
            'address' => (string) ($customer->address ?? ''),
        ];
    }

    /**
     * @param array<string, string> $oldProfile
     * @param array<string, string> $newProfile
     * @param array<int, string> $changedFields
     */
    private function notifyAdminsOfProfileUpdate(
        Customer $customer,
        array $oldProfile,
        array $newProfile,
        array $changedFields
    ): void {
        try {
            $admins = User::role('admin')->get();
        } catch (RoleDoesNotExist) {
            return;
        }

        if ($admins->isEmpty()) {
            return;
        }

        Notification::sendNow(
            $admins,
            new CustomerProfileUpdatedNotification(
                (string) $customer->name,
                (int) $customer->id,
                $oldProfile,
                $newProfile,
                array_values($changedFields),
            )
        );
    }
}
