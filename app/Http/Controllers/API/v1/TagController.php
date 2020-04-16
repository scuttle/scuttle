<?php


namespace App\Http\Controllers\API\v1;

use App\Domain;
use App\Http\Controllers\Controller;
use App\Tag;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class TagController extends Controller
{
    public function validate_tag(Domain $domain, $id)  {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:9999999999'
        ])->validate();
        try {
            $tag = Tag::withTrashed()->where('wiki_id', $domain->wiki_id)->findOrFail($id);
        } catch (ModelNotFoundException $e) { return null; }

        return $tag;
    }

    public function tag_get_tag(Domain $domain)
    {
        $tags = DB::table('tags')->select('id', 'wiki_id', 'name')->where('wiki_id', $domain->wiki_id)->get();
        $payload = $tags->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }
}
