<?php

namespace App\Http\Controllers;

use App\Models\UserBook;
use App\Traits\FileTrait;
use App\Traits\ResponseTrait;
use Illuminate\Http\Request;

class BookController extends Controller
{
    use ResponseTrait, FileTrait;
    public function index(Request $request) {
        $isFavourite = $request->is_favourite;
        $search = $request->search;
        $limit = $request->limit;

        $books = UserBook::where('user_id', auth()->id())
                ->when($isFavourite, fn($query) => $query->where('is_favourite', true))
                ->when($search, fn($query) => $query->where('title', 'Like', "%$search%"))
                ->when($limit, fn($query) => $query->limit($limit))
                ->latest('last_read_at')
                ->get(['id', 'title', 'is_favourite', 'path', 'full_link', 'mime_type', 'last_read_at', 'created_at']);

        return $this->successResponse(data:$books);
    }

    public function store(Request $request) {
        $request->validate([
            'file'  => 'required|file|mimes:pdf,doc,docx,jpg,jpeg,png|max:10240',
        ]);

        $userBook = $this->uploadBookFile($request->file);

        return $this->createdResponse('File uploaded successfully', $userBook);
    }

    public function show($bookId) {
        $book = UserBook::where([
            'user_id' => auth()->id(),
            'id' =>$bookId,
        ])->first();

        if (!$book) {
            return $this->notFoundResponse('Book not found');
        }

        $book->last_read_at = now();
        $book->save();

        return $this->successResponse('File uploaded successfully', $book);
    }

    public function toggleFavourite($bookId) {
        $book = UserBook::where([
            'user_id' => auth()->id(),
            'id' =>$bookId
        ])->first();

        if (!$book) {
            return $this->notFoundResponse('Book not found');
        }

        $book->is_favourite = $book->is_favourite ? false : true;

        $book->save();

        return $this->successResponse(data:$book);
    }

    public function destroy($bookId) {
        $book = UserBook::where([
            'user_id' => auth()->id(),
            'id' =>$bookId
        ])->first();

        if (!$book) {
            return $this->notFoundResponse('Book not found');
        }

        return $this->successMessageResponse('Book deleted successfully.');
    }
}
