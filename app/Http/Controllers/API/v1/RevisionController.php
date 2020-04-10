<?php


namespace App\Http\Controllers\API\v1;


use App\Http\Controllers\Controller;
use App\Domain;
use App\Revision;
use Illuminate\Validation\Rule;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

class RevisionController extends Controller
{
    public function validate_revision(Domain $domain, $id)
    {
        $rule['id'] = $id;
        Validator::make($rule, [
            'id' => 'required|integer|min:1|max:9999999999'
        ])->validate();
        try {
            $revision = Revision::findOrFail($id);
        } catch (ModelNotFoundException $e) {
            return null;
        }
        // Little check to see if this revision is within the scope of this domain.
        // We're not returning a different status in the interest of customer privacy.
        if($revision->page->wiki_id != $domain->wiki_id) { return null; }
        return $revision;
    }

    public function revision_get_revision_ID(Domain $domain, $id)
    {
        $revision = $this->validate_revision($domain, $id);
        if (!$revision) { return response()->json(['message' => 'A revision with that ID was not found in this wiki.'])->setStatusCode(404); }

        $revision->metadata = json_decode($revision->metadata, true);
        unset($revision->content); unset($revision->searchtext); unset($revision->page);
        $payload = $revision->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }

    public function revision_get_revision_ID_full(Domain $domain, $id)
    {
        $revision = $this->validate_revision($domain, $id);
        if (!$revision) { return response()->json(['message' => 'A revision with that ID was not found in this wiki.'])->setStatusCode(404); }

        $revision->metadata = json_decode($revision->metadata, true);
        unset($revision->searchtext);
        $payload = $revision->toJson();
        return response($payload)->header('Content-Type', 'application/json');
    }



}
