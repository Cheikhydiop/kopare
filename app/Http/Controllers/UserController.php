<?php
namespace App\Http\Controllers;

use App\Models\UserMysql;
use Illuminate\Http\Request;
use App\Exceptions\UserException;
use Illuminate\Support\Facades\Gate;
use App\Http\Requests\StoreUserRequest;
use App\Http\Requests\UpdateUserRequest;
use App\Interfaces\UserServiceInterface;

/**
 * @OA\PathItem(path="/api/users")
 */
class UserController extends Controller
{
    /**
     * @OA\Get(
     *     path="/api/users",
     *     summary="Obtenir tous les utilisateurs",
     *     @OA\Response(
     *         response="200",
     *         description="Succès",
     *         @OA\JsonContent(type="array", @OA\Items(ref="#/components/schemas/User"))
     *     )
     * )
     */
    public function index(Request $request)
    {
        return $this->userService->getAllUsers($request);
    }

    /**
     * @OA\Post(
     *     path="/api/users",
     *     summary="Créer un nouvel utilisateur",
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/StoreUserRequest")
     *     ),
     *     @OA\Response(response="201", description="Utilisateur créé avec succès")
     * )
     */
    public function store(StoreUserRequest $request)
    {
        $fonction = $request->input('fonction');
        if (Gate::allows('create', [UserMysql::class, $fonction]));
        $data = $request->validated();
        $user = $this->userService->createUser($data);
        throw UserException::userCreated($user);
    }

    /**
     * @OA\Get(
     *     path="/api/users/{id}",
     *     summary="Afficher un utilisateur",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="200", description="Détails de l'utilisateur")
     * )
     */
    public function show(string $id)
    {
        $user = $this->userService->getUserById($id);
        return response()->json($user);
    }

    /**
     * @OA\Put(
     *     path="/api/users/{id}",
     *     summary="Mettre à jour un utilisateur",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\RequestBody(
     *         required=true,
     *         @OA\JsonContent(ref="#/components/schemas/UpdateUserRequest")
     *     ),
     *     @OA\Response(response="200", description="Utilisateur mis à jour avec succès")
     * )
     */
    public function update(UpdateUserRequest $request, string $id)
    {
        $data = $request->validated();
        $updatedUser = $this->userService->updateUser($id, $data);
        throw UserException::userUpdated($updatedUser);
    }

    /**
     * @OA\Delete(
     *     path="/api/users/{id}",
     *     summary="Supprimer un utilisateur",
     *     @OA\Parameter(name="id", in="path", required=true, @OA\Schema(type="string")),
     *     @OA\Response(response="204", description="Utilisateur supprimé avec succès")
     * )
     */
    public function destroy(string $id)
    {
        $users = $this->userService->deleteUser($id);
        throw UserException::userDeleted($users);
    }

    /**
     * @OA\Schema(
     *     schema="User",
     *     type="object",
     *     @OA\Property(property="id", type="string", example="1"),
     *     @OA\Property(property="name", type="string", example="John Doe"),
     *     @OA\Property(property="email", type="string", example="john@example.com")
     * )
     */
}
