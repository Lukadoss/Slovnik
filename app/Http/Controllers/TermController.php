<?php

namespace App\Http\Controllers;

use App\District;
use App\Noun;
use App\Part_of_speech;
use App\Term;
use App\Verb;
use Illuminate\Http\File;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Storage;

class TermController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth', ['except' => 'showTerms']);
    }

    public function addTerm(){
        $this->validate(request(), [
            'term' => 'required',
            'pronunciation' => 'required',
            'meaning' => 'required',
            'district' => 'required',
            'pos' => 'required',
            'fileUp' => 'file|mimes:mpga,wav|max:10000'
        ]);

        $pos = Part_of_speech::create([
            'part_of_speech' => request('pos')
        ]);

        if(request('pos') === 'Podstatné jméno'){
            Noun::create([
                'gender' => request('noun_gender'),
                'sufix' => request('noun_sufix'),
                'part_of_speech_id' => $pos->id
            ]);
        }elseif (request('pos') === 'Sloveso') {
            Verb::create([
                'aspect' => request('verb_aspect'),
                'valence' => request('verb_valence'),
                'part_of_speech_id' => $pos->id
            ]);
        }
        $file = request('fileUp');
        isset($file) ? $filePath = Storage::putFile('audio', new File($file)) : $filePath=null;

        Term::create([
            'term' => request('term'),
            'pronunciation' => request('pronunciation'),
            'origin' => request('origin'),
            'meaning' => request('meaning'),
            'symptom' => request('symptom'),
            'context' => request('context'),
            'exemplification' => request('exemplification'),
            'examples' => request('example'),
            'synonym' => request('synonym'),
            'thesaurus' => request('thesaurus'),
            'audio_path' => $filePath,
            'user_id' => auth()->user()->id,
            'district_id' => request('district'),
            'part_of_speech_id' => $pos->id
        ]);

        return redirect()->back()->with('success', 'Heslo úspěšně přidáno.');

    }

    public function checkTerms(){
        $terms = Term::where('accepted', '0')->get();
        foreach ($terms as $key => $term){
            if(!auth()->user()->isTermViable($term->district->region)){
                unset($terms[$key]);
            }
        }

        return view('term.termCheck', compact('terms'));
    }

    public function showEdit($id){
        $term = Term::findOrFail($id);
        if(!auth()->user()->isTermViable($term->district->region))
            return redirect()->to('/list')->with('info', 'K takové operaci nemáte přístup');

        $towns = District::all();
        $pos = Part_of_speech::findOrFail($term->part_of_speech_id);
        $noun = Noun::where('part_of_speech_id', $pos->id)->first();
        $verb = Verb::where('part_of_speech_id', $pos->id)->first();

        return view('term.editTerm', compact('term', 'towns', 'pos', 'noun', 'verb'));
    }

    public function deleteTerm($id){
        $term = Term::findOrFail($id);
        if(!auth()->user()->isTermViable($term->district->region))
            return redirect()->to('/list')->with('info', 'K takové operaci nemáte přístup');

        $pos = Part_of_speech::findOrFail($term->part_of_speech_id);

        if($noun = Noun::where('part_of_speech_id', $pos->id)->first()){
            Noun::destroy($noun->id);
        }elseif ($verb = Verb::where('part_of_speech_id', $pos->id)->first()) {
            Verb::destroy($verb->id);
        }
        Storage::delete($term->audio_path);
        Term::destroy($id);
        Part_of_speech::destroy($pos->id);

        return redirect()->to('/term/waiting')->with('info', 'Heslo úspěšně smazáno');
    }

    public function acceptTerm($id){
        $term = Term::findOrFail($id);
        if(!auth()->user()->isTermViable($term->district->region))
            return redirect()->to('/term/waiting')->with('info', 'K takové operaci nemáte přístup');

        $term->accepted = 1;
        $term->save();

        return redirect()->back()->with('success', 'Heslo bylo schváleno a publikováno.');
    }

    public function editTerm($termId){
        $this->validate(request(), [
            'term' => 'required',
            'pronunciation' => 'required',
            'meaning' => 'required',
            'district' => 'required',
            'pos' => 'required',
            'fileUp' => 'file|mimes:mpga,wav|max:10000'
        ]);

        $term = Term::findOrFail($termId);
        if(!auth()->user()->isTermViable($term->district->region))
            return redirect()->to('/list')->with('info', 'K takové operaci nemáte přístup');

        $pos = Part_of_speech::findOrFail($term->part_of_speech_id);

        if($noun = Noun::where('part_of_speech_id', $pos->id)->first()){
            Noun::destroy($noun->id);
        }elseif ($verb = Verb::where('part_of_speech_id', $pos->id)->first()) {
            Verb::destroy($verb->id);
        }

        $pos->part_of_speech = request('pos');
        $pos->save();

        if(request('pos') === 'Podstatné jméno'){
            Noun::create([
                'gender' => request('noun_gender'),
                'sufix' => request('noun_sufix'),
                'part_of_speech_id' => $pos->id
            ]);
        }elseif (request('pos') === 'Sloveso') {
            Verb::create([
                'aspect' => request('verb_aspect'),
                'valence' => request('verb_valence'),
                'part_of_speech_id' => $pos->id
            ]);
        }

        $term->term = request('term');
        $term->pronunciation = request('pronunciation');
        $term->origin = request('origin');
        $term->meaning = request('meaning');
        $term->symptom = request('symptom');
        $term->context = request('context');
        $term->exemplification = request('exemplification');
        $term->examples = request('example');
        $term->synonym = request('synonym');
        $term->thesaurus = request('thesaurus');
        $term->user_id = auth()->user()->id;
        $term->district_id = request('district');
        $term->part_of_speech_id = $pos->id;

        if(request('fileUp') !== null){
            Storage::delete($term->audio_path);
            $filePath = Storage::putFile('audio', new File(request('fileUp')));
            $term->audio_path = $filePath;
        }

        $term->save();

        return redirect()->back()->with('success', 'Heslo úspěšně upraveno.');
    }

    public function getNonCheckTermNum(){
        $terms = Term::where('accepted', '0')->get();
        foreach ($terms as $key => $term){
            if(!auth()->user()->isTermViable($term->district->region)){
                unset($terms[$key]);
            }
        }

        return count($terms);
    }

    public function download($id){
        $term = Term::findOrFail($id);
        if(strlen($term->audio_path)>0)
            return response()->download(Storage::disk('local')->getDriver()->getAdapter()->getPathPrefix().$term->audio_path);
        return abort(404);
    }
}
