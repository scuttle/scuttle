<?php


namespace App\Http\Controllers\API\v1;

use App\Post;
use App\Http\Controllers\Controller;
use App\Domain;
use App\Page;
use App\WikidotUser;
use App\Revision;
use GuzzleHttp\Client;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

class WikidotUserController extends Controller
{
    public function validate_wikidot_user($id)
    {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:9999999999'
        ])->validate();
        try {
            $wikidotuser = WikidotUser::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
        $wikidotuser->metadata = json_decode($wikidotuser->metadata, true);
        return $wikidotuser;
    }

    public function wikidotuser_get_wikidotuser_ID(Domain $domain, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }
        $payload = $wikidotuser->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_get_wikidotuser_username_USERNAME(Domain $domain, $username)
    {
        $rule['username'] = $username;
        Validator::make($rule, [
            'username' => 'required|string|min:1|max:70'
        ])->validate();

        $wikidotuser = WikidotUser::where('username',$username)->get();

        if($wikidotuser->isEmpty()) { return response()->json(['message' => 'No user exists in the database with that Wikidot username.'])->setStatusCode(404); }
        foreach($wikidotuser as $w) {
            $w->metadata = json_decode($w->metadata, true);
        }

        $payload = $wikidotuser->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_get_wikidotuser_ID_avatar(Domain $domain, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }
        $avatar_path = $wikidotuser->avatar_path;
        $client = new Client();
        $response = $client->request('GET', $avatar_path);
        $avatar = $response->getBody();
        $content_type = $response->getHeader('Content-Type');
        $payload = [
          'Content-Type' => $content_type,
          'payload' => base64_encode($avatar)
        ];
        return response(json_encode($payload))->header('Content-Type', 'application/json');
    }

    public function wikidotuser_get_wikidotuser_ID_pages(Domain $domain, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }

        $pages = $wikidotuser->pages()
            ->select('id','wd_page_id','slug','created_at')
            ->where('wiki_id',$domain->wiki_id)
            ->get();

        $payload = $pages->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_post_wikidotuser_ID_pages(Domain $domain, Request $request, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }
        Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;
        $direction = $request->direction ?? 'desc';

        $pages = $wikidotuser->pages()
            ->where('wiki_id',$domain->wiki_id)
            ->offset($offset)->limit($limit)->orderBy('wd_page_id', $direction)
            ->get();

        foreach($pages as $page) {
            $page->metadata = json_decode($page->metadata, true);
        }

        $payload = $pages->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_get_wikidotuser_ID_posts(Domain $domain, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }

        $posts = $wikidotuser->posts()
            ->select('id','wd_post_id','thread_id')
            ->get();

        // TODO: Exclude posts by wiki_id. This doesn't break structure so doesn't block API from shipping.
        // This code is too slow, maybe a view? Or maybe we look at including wiki_id everywhere.
//        foreach($posts as $post) {
//            if($post->thread->forum->wiki_id != $domain->wiki_id) {
//                unset($post);
//            }
//        }

        $payload = $posts->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_post_wikidotuser_ID_posts(Domain $domain, Request $request, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }
        Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;
        $direction = $request->direction ?? 'desc';

        $posts = $wikidotuser->posts()
//            ->where('wiki_id',$domain->wiki_id)  # TODO: Scope this to the wiki the API call originated from.
            ->offset($offset)->limit($limit)->orderBy('wd_post_id', $direction)
            ->get();

        foreach($posts as $post) {
            $post->metadata = json_decode($post->metadata, true);
        }

        $payload = $posts->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_get_wikidotuser_ID_revisions(Domain $domain, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }

        $revisions = $wikidotuser->revisions()
            ->select('id','wd_revision_id','page_id','created_at')
            ->get();

        // TODO: Add wiki_id to revisions (well, everywhere).
//        foreach($revisions as $revision) {
//            if($revision->wiki_id != $domain->wiki_id) {unset($revision);}
//        }

        $payload = $revisions->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_post_wikidotuser_ID_revisions(Domain $domain, Request $request, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }
        Validator::make($request->all(), [
            'limit' => 'nullable|integer|min:1|max:100',
            'offset' => 'nullable|integer|min:0',
            'direction' => ['nullable', Rule::in(['asc','desc'])],
        ])->validate();

        $limit = $request->limit ?? 20;
        $offset = $request->offset ?? 0;
        $direction = $request->direction ?? 'desc';

        $revisions = $wikidotuser->revisions()
            ->offset($offset)->limit($limit)->orderBy('wd_revision_id', $direction)
            ->get();

        foreach($revisions as $revision) {
            $revision->metadata = json_decode($revision->metadata, true);
            unset($revision->searchtext);
        }

        $payload = $revisions->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function wikidotuser_get_wikidotuser_ID_votes(Domain $domain, $id)
    {
        $wikidotuser = $this->validate_wikidot_user($id);
        if(!$wikidotuser) { return response()->json(['message' => 'No user exists in the database with that Wikidot User ID.'])->setStatusCode(404); }

        $votes = $wikidotuser->votes()
            ->get();

        // TODO: Exclude votes by wiki_id. This doesn't break structure so doesn't block API from shipping.
        // This code is too slow, maybe a view? Or maybe we look at including wiki_id everywhere.
//        foreach(votes as $vote) {
//            if($vote->page->wiki_id != $domain->wiki_id) {
//                unset($vote);
//            }
//        }

        $payload = $votes->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }
}
