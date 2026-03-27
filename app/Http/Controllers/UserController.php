<?php

namespace App\Http\Controllers;

use App\Helpers\ApiResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;


/**
 * @group User Management
 */
class UserController extends Controller
{
    /**
     * Lấy thông tin user
     *
     * API này trả về thông tin user hiện tại kèm preferences và history.
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "data": {
     *     "id": 1,
     *     "name": "Nguyen Van A",
     *     "email": "test@gmail.com"
     *   }
     * }
     */
    public function show()
    {
        $user = auth()->user()->load('preferences', 'history');

        if (!$user) {
            return ApiResponse::error(null, 'User not found', 404);
        }

        return ApiResponse::success($user);
    }
    /**
     * Cập nhật thông tin user
     *
     * @authenticated
     *
     * @bodyParam name string Tên người dùng. Example: Nguyen Van B
     * @bodyParam avatar file Ảnh đại diện (jpg, png, webp...). Max 2MB.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User updated successfully"
     * }
     */
    public function update(Request $request)
    {
        $user = auth()->user();
        $validated = $request->validate([
            'name' => 'sometimes|required|string|max:255',
            'avatar' => 'sometimes|nullable|file|max:2048|mimes:jpg,jpeg,png,webp,svg,gif',
        ]) ?? [];

        if ($request->hasFile('avatar')) {
            $path = Storage::disk('cloudinary')->putFile('avatars', $request->file('avatar'));
            $url = Storage::disk('cloudinary')->url($path);
            $validated['avatar'] = $url;
        }
        $user->update($validated);
        return ApiResponse::success($user, 'User updated successfully');
    }
    /**
     * Xoá tài khoản
     *
     * @authenticated
     *
     * API này sẽ xoá user hiện tại.
     *
     * @response 200 {
     *   "success": true,
     *   "message": "User deleted successfully"
     * }
     */
    public function destroy()
    {
        $user = auth()->user();
        $user->delete();
        return ApiResponse::success(null, 'User deleted successfully');
    }
    /**
     * Lịch sử xem phim
     *
     * @authenticated
     *
     * Trả về danh sách các nội dung user đã xem.
     *
     * @response 200 {
     *   "success": true,
     *   "data": []
     * }
     */
    public function history() {}
    /**
     * Danh sách yêu thích
     *
     * @authenticated
     *
     * @response 200 {
     *   "success": true,
     *   "data": []
     * }
     */
    public function watchlist() {}
    /**
     * Thêm vào danh sách yêu thích
     *
     * @authenticated
     *
     * @bodyParam movie_id integer required ID phim. Example: 10
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Added to watchlist"
     * }
     */
    public function addToWatchlist() {}
    /**
     * Xoá khỏi danh sách yêu thích
     *
     * @authenticated
     *
     * @urlParam id integer required ID phim. Example: 10
     *
     * @response 200 {
     *   "success": true,
     *   "message": "Removed from watchlist"
     * }
     */
    public function removeFromWatchlist($id) {}
}
