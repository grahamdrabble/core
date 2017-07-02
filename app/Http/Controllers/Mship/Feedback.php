<?php

namespace App\Http\Controllers\Mship;

use Redirect;
use Validator;
use Illuminate\Http\Request;
use App\Models\Mship\Account;
use App\Models\Mship\Feedback\Form;
use App\Models\Mship\Feedback\Answer;
use App\Events\Mship\Feedback\NewFeedbackEvent;

class Feedback extends \App\Http\Controllers\BaseController
{

    private $returnList;

    public function getFeedbackFormSelect()
    {
        return view('mship.feedback.form');
    }

    public function postFeedbackFormSelect(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'feedback_type' => 'required|exists:mship_feedback_forms,id',
        ]);
        if ($validator->fails()) {
            return Redirect::back()
                        ->withErrors($validator)
                        ->withInput();
        }

        return Redirect::route('mship.feedback.new.form', [$request->input('feedback_type')]);
    }

    public function getFeedback(Form $form)
    {
        $questions = $form->questions()->orderBy('sequence')->get();
        if (!$questions) {
            // We have no questions to display!
            return Redirect::route('mship.manage.dashboard');
        }

        // Lets parse the questions ready for inserting
        foreach ($questions as $question) {
            $question->form_html = '';
            if ($question->type->requires_value == true) {
                if (isset($question->options['values'])) {
                    foreach ($question->options['values'] as $key => $value) {
                        $selected = '';
                        if (old($question->slug) == $value) {
                            $selected = 'checked';
                        }
                        $question->form_html .= sprintf($question->type->code, $question->slug, old($question->slug), $value, $value, $selected);
                    }
                    continue;
                }
            // No values, so we cant use it :/
            continue;
            }

            $question->form_html .= sprintf($question->type->code, $question->slug, old($question->slug));
        }

        return view('mship.feedback.form', ['form' => $form, 'questions' => $questions]);
    }

    public function postFeedback(Form $form, Request $request)
    {
        $questions = $form->questions;
        $cidfield = null;
        // Make the validation rules
        $ruleset = [];
        $errormessages = [];
        $answerdata = [];

        foreach ($questions as $question) {
            $rules = [];

            if ($question->type->name == 'userlookup') {
                $cidfield = $question->slug;
            }

            // Proccess rules

            if ($question->type->rules != null) {
                $rules = explode('|', $question->type->rules);
            }

            if ($question->required) {
                $rules[] = 'required';
            }
            if (count($rules > 0)) {
                $ruleset[$question->slug] = join($rules, '|');
            }

            // Process errors
            foreach ($rules as $rule) {
                $automaticRuleErrors = ['required', 'exists', 'integer'];
                if (!array_search($rule, $automaticRuleErrors)) {
                    $errormessages[$question->slug.'.'.$rule] = "Looks like you answered '".$question->question."' incorrectly. Please try again.";
                }
            }
            $errormessages[$question->slug.'.required'] = "You have not supplied an answer for '".$question->question."'.";
            $errormessages[$question->slug.'.exists'] = 'This user was not found. Please ensure that you have entered the CID correctly, and that they are a UK member';
            $errormessages[$question->slug.'.integer'] = 'You have not entered a valid integer.';

            // Add the answer to the array, ready for inserting
            $answerdata[] = new Answer([
              'question_id' => $question->id,
              'response' => $request->input($question->slug),
            ]);
        }

        $validator = Validator::make($request->all(), $ruleset, $errormessages);
        if ($validator->fails()) {
            return back()->withErrors($validator)
                        ->withInput();
        }

        if (!$cidfield) {
            // No one specified a user lookup field!
            return Redirect::route('mship.manage.dashboard')
                    ->withError("Sorry, we can't process your feedback at the moment. Please check back later.");
        }

        // Make new feedback
        $account = Account::find($request->input($cidfield));
        $feedback = $account->feedback()->create([
            'submitter_account_id' => \Auth::user()->id,
            'form_id' => $form->id,
        ]);

        // Add in the answers
        $feedback->answers()->saveMany($answerdata);
        event(new NewFeedbackEvent($feedback));

        return Redirect::route('mship.manage.dashboard')
                ->withSuccess('Your feedback has been recorded. Thank you!');
    }

    public function getUserSearch($name, Request $request)
    {

      $matches = Account::whereRaw("CONCAT(`name_first`, ' ',`name_last`) LIKE '%".$name."%'")->limit(5)->with(['states'])->get(['id', 'name_first', 'name_last']);

      $this->returnList = collect();

      $matches->transform(function($user, $key){

          foreach ($user->states as $key => $state) {
            if($state->is_permanent){
              if($state->code = "INTERNATIONAL" && ($state->region->first() == "*" || $state->division->first() == "*")){
                $user->state = "Intl.";
              }else{
                $user->state = $state->region->first() . '/' . $state->division->first();
              }
            }
          }

          $this->returnList->push(collect([
            'cid' => $user->id,
            'name' => $user->real_name,
            'status' => $user->state
          ]));

          return $user;
      });
      $matches = null;

      return response()->json($this->returnList);
    }
}
