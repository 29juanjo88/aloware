<?php

namespace App\Http\Controllers;

use App\Models\Comments;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;


class CommentsController extends Controller
{
    public function index(): \Illuminate\Http\JsonResponse
    {
        $tree = Comments::getAllComments();
        return response()->json($tree, 200);
    }

    public function show($id): \Illuminate\Http\JsonResponse
    {
        $tree = Comments::getCommentsOf($id);
        return response()->json($tree, 200);
    }

    public function store(Request $request): \Illuminate\Http\JsonResponse|array
    {
        $payload = $request->all();
        $validation = Validator::make($payload,[
            'name' => 'required',
            'message' => 'required',
        ]);

        if($validation->fails()){
            return response()->json($validation->errors(), 400);
        }
        if(empty(Comments::getComment($payload['parent_comment_id']))){
            $payload['parent_comment_id'] = NULL;
        }

        $sql = "INSERT INTO comments (name,message,parent_comment_id, created_at, updated_at) VALUES (?,?,?,?,?)";
        DB::insert($sql, [$payload['name'], $payload['message'], $payload['parent_comment_id'], now(), now()]);
        $id = DB::getPdo()->lastInsertId();
        return Comments::getComment($id);
    }

    public function update(Request $request, $id): \Illuminate\Http\JsonResponse|array
    {
        $payload = $request->all();

        $payload['parent_comment_id'] = $payload['parent_comment_id'] ?? NULL;

        if($payload['parent_comment_id'] == $id){
            return response()->json(['error' => 'Circular reference'], 400);
        }

        $sql = "UPDATE comments SET name = :name, message = :message, parent_comment_id = :parent_comment_id WHERE id = :id";
        DB::update($sql, [
            'name' => $payload['name'],
            'parent_comment_id' => $payload['parent_comment_id'],
            'message' => $payload['message'],
            'id' => $id,
        ]);
        return Comments::getCommentsOf($id);
    }

    public function destroy($id): \Illuminate\Http\JsonResponse
    {
        $sql = "DELETE FROM comments WHERE id = :id";
        DB::delete($sql, ['id' => $id]);
        return response()->json([], 204); // 204 no content
    }

}
