<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Auth;

class UserService
{
    public function list($request, array $filters = [])
    {
        $query = User::with(['roles'])
            ->when(isset($filters['search']), function ($query) use ($filters) {
                $query->where(function ($q) use ($filters) {
                    $q->where('name', 'like', '%' . $filters['search'] . '%')
                        ->orWhere('email', 'like', '%' . $filters['search'] . '%');
                });
            })
            ->when(isset($filters['role']), function ($q) use ($filters) {
                $q->whereHas('roles', fn($roleQ) => $roleQ->where('name', $filters['role']));
            })
            ->orderByDesc('id');

        $perPage = $filters['per_page'] ?? 15;
        $users = $query->paginate($perPage);

        // Format the collection
        $formatted = $users->getCollection()->map(function ($user) {
            $roleNames = $user->roles()->pluck('name')->implode(', ');
            return [
                'id' => $user->id,
                'name' => $user->name,
                'email' => $user->email,
                'roles' => $roleNames,
                'section' => $user['section'],
                'created_at' => $user->created_at->format('Y-m-d'),
            ];
        });

        // Swap original collection with formatted
        $users->setCollection($formatted);

        return $users;
    }

    public function create(array $data)
    {
        $user = User::create([
            'name' => $data['name'],
            'email' => $data['email'],
            'password' => Hash::make($data['password']),
            'email_verified_at' => now(),
            'section' => $data['section'] ?? null,
        ]);

        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }
        $roleNames = $user->roles()->pluck('name')->implode(', ');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roleNames,
            'section' => $user['section'],
            'created_at' => $user->created_at->format('Y-m-d'),
        ];
    }

    public function update(User $user, array $data)
    {
        // Update basic info
        $user->update(array_diff_key($data, ['password' => '', 'roles' => '']));

        // Update password if provided
        if (isset($data['password']) && $data['password']) {
            $user->update(['password' => Hash::make($data['password'])]);
        }

        // Sync roles if provided
        if (isset($data['roles'])) {
            $user->syncRoles($data['roles']);
        }

        $roleNames = $user->roles()->pluck('name')->implode(', ');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roleNames,
            'section' => $user['section'],
            'created_at' => $user->created_at->format('Y-m-d'),
        ];
    }

    public function delete(User $user): bool
    {
        // Check if user is trying to delete themselves
        if (Auth::id() === $user->id) {
            throw new \Exception('You cannot delete your own account');
        }

        // Remove roles
        $user->roles()->detach();

        // Delete user
        return $user->delete();
    }

    public function show(User $user)
    {
        $roleNames = $user->roles()->pluck('name')->implode(', ');

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'roles' => $roleNames,
            'section' => $user['section'],
            'created_at' => $user->created_at->format('Y-m-d'),
        ];
    }
}
