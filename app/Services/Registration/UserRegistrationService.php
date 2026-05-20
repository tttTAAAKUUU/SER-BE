<?php

namespace App\Services\Registration;

use App\Models\Business\Business;
use App\Models\Location\Location;
use App\Models\ServiceProvider\ServiceProviderProfile;
use App\Models\Administrator\AdministratorProfile;
use App\Models\User\User;
use App\Models\User\UserProfile;
use App\Services\Registration\Exceptions\EmailTakenException;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;

class UserRegistrationService
{
    public function registerCustomer(array $data): UserProfile
    {
        return DB::transaction(function () use ($data) {
            $this->ensureEmailIsUnique($data['email']);

            $user = User::create([
                'name'     => $data['first_name'] . ' ' . $data['last_name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return UserProfile::create([
                'user_id'    => $user->id,
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'phone'      => $data['phone'],
                'dob'        => $data['dob'],
                'gender'     => $data['gender'],
            ]);
        });
    }

    public function registerBusiness(array $user, array $business, array $location): Business
    {
        return DB::transaction(function () use ($user, $business, $location) {
            $this->ensureEmailIsUnique($user['email']);

            $user = User::create([
                'name'     => $user['first_name'] . ' ' . $user['last_name'],
                'email'    => $user['email'],
                'password' => Hash::make($user['password']),
            ]);

            $location = Location::create([
                'street_address' => $location['street_address'],
                'suburb'         => $location['suburb'],
                'city'           => $location['city'],
                'lat'            => $location['lat'],
                'lng'            => $location['lng'],
                'postal_code'    => $location['postal_code'],
            ]);

            return Business::create([
                'user_id'       => $user->id,
                'location_id'   => $location->id,
                'name'          => $business['name'],
                'email'         => $business['email'],
                'phone'         => $business['phone'],
                'opening_time'  => $business['opening_time'],
                'closing_time'  => $business['closing_time'],
            ]);
        });
    }

    public function registerServiceProvider(array $data): ServiceProviderProfile
    {
        return DB::transaction(function () use ($data) {
            $this->ensureEmailIsUnique($data['email']);

            $user = User::create([
                'name'     => $data['first_name'] . ' ' . $data['last_name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            $profileData = [
                'user_id'    => $user->id,
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
                'phone'      => $data['phone'],
                'dob'        => $data['dob'],
                'gender'     => $data['gender'],
                'bio'        => $data['bio'] ?? null,
                'service_area' => $data['service_area'] ?? null,
            ];

            $profile = ServiceProviderProfile::create($profileData);

            // Attach service categories if provided
            if (! empty($data['service_category_ids'])) {
                $profile->serviceCategories()->attach($data['service_category_ids']);
            }

            return $profile;
        });
    }

    public function registerAdministrator(array $data): AdministratorProfile
    {
        return DB::transaction(function () use ($data) {
            $this->ensureEmailIsUnique($data['email']);

            $user = User::create([
                'name'     => $data['first_name'] . ' ' . $data['last_name'],
                'email'    => $data['email'],
                'password' => Hash::make($data['password']),
            ]);

            return AdministratorProfile::create([
                'user_id'    => $user->id,
                'first_name' => $data['first_name'],
                'last_name'  => $data['last_name'],
            ]);
        });
    }

    private function ensureEmailIsUnique(string $email): void
    {
        if (User::whereRaw('LOWER(email) = ?', [strtolower($email)])->exists()) {
            throw new EmailTakenException($email);
        }
    }
}