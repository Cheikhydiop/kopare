<?php

namespace App\Repositories;

use App\Models\UserMysql;
use App\Events\UserCreated;
use App\Facades\UserFirebase;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use App\Services\LocalStorageService;
use App\Interfaces\UserRepositoryInterface;

class UserRepository implements UserRepositoryInterface
{
    protected $LocalStorageService;
    public function __construct(LocalStorageService $LocalStorageService)
    {
        $this->LocalStorageService = $LocalStorageService;
    }

    public function getAllUsers(array $filters)
    {
        $query = UserFirebase::query();
        if (!empty($filters['role'])) {
            $query->where('fonction', $filters['role']);
        }
        return $query->get();
    }

    // public function createUser(array $data)
    // {
    //     DB::beginTransaction();
    //     if (isset($data['password'])) {
    //         $data['password'] = Hash::make($data['password']);
    //     }
    //     $originalFileName = $data['photo']->getClientOriginalName();
    //     $localPath = $this->LocalStorageService->storeImageLocally('images/users', $originalFileName) ;
    //     $data['photo'] = $localPath;
    //     $userMysql = UserMysql::create($data);
    //     $firebaseUserId = UserFirebase::create($data);
    //     $data = UserFirebase::find($firebaseUserId);
    //     $userMysql->id = $firebaseUserId;
    //     $userMysql->save();
    //     DB::commit();
    //     event(new UserCreated($userMysql, $firebaseUserId));
    //     return $userMysql;
    // }

    public function createUser(array $data)
{
    DB::beginTransaction();
    
    try {
        // Hash the password if it exists
        if (isset($data['password'])) {
            $data['password'] = Hash::make($data['password']);
        }

        // Check if a photo has been uploaded
        if (isset($data['photo']) && $data['photo'] instanceof \Illuminate\Http\UploadedFile) {
            $originalFileName = $data['photo']->getClientOriginalName();
            $localPath = $this->LocalStorageService->storeImageLocally('images/users', $originalFileName);
            $data['photo'] = $localPath;
        } else {
            // Handle the case where no photo is uploaded
            $data['photo'] = 'default.jpg'; // Set a default photo or handle as needed
        }

        // Create the user in MySQL
        $userMysql = UserMysql::create($data);

        // Create the user in Firebase
        $firebaseUserId = UserFirebase::create($data);

        // Update user ID in MySQL
        $userMysql->id = $firebaseUserId;
        $userMysql->save();

        // Commit the transaction
        DB::commit();

        // Trigger the UserCreated event
        event(new UserCreated($userMysql, $firebaseUserId));

        return $userMysql;

    } catch (\Exception $e) {
        DB::rollBack(); // Rollback on error
        throw $e; // Rethrow the exception for higher-level handling
    }
}


    public function getUserById(string $id)
    {
        return UserFirebase::find($id);
    }

    public function updateUser(string $id, array $data): ?array
    {
        DB::beginTransaction();
        $userMysql = UserMysql::find($id);
        if ($userMysql) {
            $userMysql->update($data);
        }
        $userFirebase = UserFirebase::find($id);
        if ($userFirebase) {
            UserFirebase::update($id, $data);
        }
        DB::commit();
        return [
            'firebase' => UserFirebase::find($id),
        ];
    }

    public function deleteUser(string $id): bool
    {
        DB::beginTransaction();
        $deletedMysql = UserMysql::destroy($id);
        $deletedFirebase = UserFirebase::delete($id);
        DB::commit();
        return $deletedMysql && $deletedFirebase;
    }
}
