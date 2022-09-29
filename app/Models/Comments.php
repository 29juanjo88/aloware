<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\DB;

class Comments extends Model
{
    use HasFactory;

    public static function getComment($id): array
    {
        $sql = "SELECT * FROM comments WHERE id = :id";
        return DB::select($sql, ['id' => $id]);
    }

    public static function getAllComments(): array
    {
        return self::getCommentsOf(NULL);
    }
    public static function getCommentsOf($parentID = NULL): array
    {
        if($parentID == NULL){
            $comments = DB::select('select * from comments order by parent_comment_id asc, id');
        }else {
            /// If we just want to traverse the comments from a given comment, this query will have better performance since we will skip all the comments out of
            /// $parentID's subtree
            $comments = DB::select("
                WITH recursive parent_comment (id, NAME, message, parent_comment_id) AS
                (
                       SELECT id,
                              name,
                              message,
                              parent_comment_id
                       FROM   comments
                       WHERE  parent_comment_id = :parentID
                       UNION ALL
                       SELECT     child.id,
                                  child.NAME,
                                  child.message,
                                  child.parent_comment_id
                       FROM       comments child
                       INNER JOIN parent_comment
                       ON         child.parent_comment_id = parent_comment.id )
                SELECT parent_comment_id,
                       id,
                       name,
                       message
                FROM   parent_comment;
            ", ['parentID' => $parentID]);
            $comments[] = (object) self::getComment($parentID)[0]; // the parent itself
            $comments = (object) array_reverse($comments); // we want the parent to be the first element
        }

        $commentsTree = [];
        $commentRefs = [];
        $processedComments = [];
        foreach ($comments as $comment) {
            $processedComments[$comment->id] = true;
            if (!isset($processedComments[$comment->parent_comment_id])) {
                //first level comments
                if($parentID != NULL && $comment->id != $parentID){
                    continue; // when specified $parentID, Other top level comments should be ignored
                }

                $commentsTree[$comment->id] = $comment;
                $commentRefs[$comment->id] = &$commentsTree[$comment->id];
            } else {
                if(isset($commentRefs[$comment->parent_comment_id])){
                    $parent = $commentRefs[$comment->parent_comment_id];
                    if (!isset($parent->children)) {
                        $parent->children = [];
                    }
                    $parent->children[$comment->id] = $comment;
                    $commentRefs[$comment->id] = &$parent->children[$comment->id];
                }else{
                    // parent comment is not in the tree
                    $commentsTree[$comment->id] = $comment;
                    $commentRefs[$comment->id] = &$commentsTree[$comment->id];
                }
            }
        }
        foreach ($commentsTree as $comment) {
            if ($comment->parent_comment_id and isset($commentsTree[$comment->parent_comment_id])) {
                $commentsTree[$comment->parent_comment_id]->children[] = $comment;
            }
        }
        return $commentsTree;
    }

}
