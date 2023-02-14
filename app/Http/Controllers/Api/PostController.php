<?php

namespace App\Http\Controllers\Api;

use App\Models\Post;
use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Http\Resources\PostResource;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Storage;

class PostController extends Controller
{
    //
    public function index()
    {
        $posts = Post::latest()->paginate(5);

        return new PostResource(true, 'List data', $posts);
    }

    public function store(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'image' => 'required|image|mimes:jpeg,png,jpg,gif,svg|max:2048',
            'title' => 'required',
            'content' => 'required',
        ]);

        // if ($validator->fails()) {
        //     return new PostResource(false, $validator->errors(), 422);
        // }
        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        // upload image
        $image = $request->file('image');
        $image->storeAs('public/images', $image->hashName());

        //create post
        $post = Post::create([
            'image' => $image->hashName(),
            'title' => $request->title,
            'content' => $request->content,
        ]);

        return new PostResource(true, 'Post created successfully', $post);
    }

    public function show($id)
    {
        $post = Post::findOrFail($id);

        return new PostResource(true, 'Detail data', $post);
    }

    public function update(Request $request, Post $post)
    {
        // validation
        $validator = Validator::make($request->all(), [
            'title' => 'required',
            'content' => 'required',
        ]);

        if ($validator->fails()) {
            return response()->json($validator->errors(), 422);
        }

        if ($request->hasFile('image')){
            // upload image
            $image = $request->file('image');
            $image->storeAs('public/images', $image->hashName());

            // delete old image
            Storage::delete('public/images/' . $post->image);

            // update post with new image
            $post->update([
                'image' => $image->hashName(),
                'title' => $request->title,
                'content' => $request->content,
            ]);
        } else {
            // update post without image
            $post->update([
                'title' => $request->title,
                'content' => $request->content,
            ]);
        }
        return new PostResource(true, 'Post updated successfully', $post);
    }

    public function destroy(Post $post)
    {
        // soft delete image
        Storage::delete('public/images/' . $post->image);

        // delete post
        $post->delete();

        return new PostResource(true, 'Post deleted successfully', null);
    }
}
