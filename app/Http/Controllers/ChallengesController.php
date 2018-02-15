<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Illuminate\Support\Facades\Validator;
use Purifier;
use Response;
use Auth;
use Image;

use App\Category;
use App\Challenge;
use App\Cbind;
use App\Ptbind;
use App\Upload;
//use App\Team;
use App\User;
use App\Workspace;


class ChallengesController extends Controller
{
  public function __construct()
  {
    $this->middleware('jwt.auth', ['only' => ['store', 'joinChallenge', 'uploadFile']]);
  }

  public function index($count)
  {
    $challenges = Challenge::where('challenges.status', 'Approved')->join('workspaces', 'challenges.spaceID', '=', 'workspaces.id')
    ->select(
      'challenges.id',
      'challenges.challengeImage',
      'challenges.challengeTitle',
      'challenges.challengeContent',
      'challenges.challengeSlug',
      'challenges.spaceID',
      'challenges.startDate',
      'challenges.endDate',
      'workspaces.logo',
      'workspaces.name',
      'workspaces.city'
    )
    ->orderBy('challenges.created_at', 'DESC')
    ->paginate($count);

    foreach($challenges as $key => $challenge)
    {
      $categories = Cbind::where('cbinds.challengeID', $challenge->id)->join('categories', 'cbinds.categoryID', '=', 'categories.id')
        ->select(
          'categories.id',
          'categories.categorySlug',
          'categories.categoryName',
          'categories.categoryColor',
          'categories.categoryTextColor'
        )
        ->get();

      $challenge->categories = $categories;
      $challenge->challengeContent = substr(strip_tags($challenge->challengeContent), 0, 200);
    }

    return Response::json(['challenges' => $challenges]);
  }

  public function upcoming($count)
  {
    $challenges = Challenge::whereDate('challenges.startDate', '>', date('Y-m-d'))->where('challenges.status', 'Approved')->join('workspaces', 'challenges.spaceID', '=', 'workspaces.id')
    ->select(
      'challenges.id',
      'challenges.challengeImage',
      'challenges.challengeTitle',
      'challenges.challengeContent',
      'challenges.challengeSlug',
      'challenges.spaceID',
      'challenges.startDate',
      'challenges.endDate',
      'workspaces.logo',
      'workspaces.name',
      'workspaces.city'
    )
    ->orderBy('created_at', 'DESC')
    ->paginate($count);

    foreach($challenges as $key => $challenge)
    {
      $categories = Cbind::where('cbinds.challengeID', $challenge->id)->join('categories', 'cbinds.categoryID', '=', 'categories.id')
        ->select(
          'categories.id',
          'categories.categorySlug',
          'categories.categoryName',
          'categories.categoryColor',
          'categories.categoryTextColor'
        )
        ->get();

      $challenge->categories = $categories;
      $challenge->challengeContent = substr(strip_tags($challenge->challengeContent), 0, 200);

    }

    return Response::json(['challenges' => $challenges]);
  }

  public function store(Request $request)
  {
    $rules = [
      'challengeImage' => 'required',
      'challengeTitle' => 'required',
      'challengeContent' => 'required',
      'challengeCategories' => 'required'
    ];

    $validator = Validator::make(Purifier::clean($request->all()), $rules);
    if($validator->fails())
    {
      return Response::json(['error' => 'Please fill out all fields.']);
    }

    $user = Auth::user();

    $spaceID = $user->spaceID;
    $challengeImage = $request->file('challengeImage');
    $challengeTitle = $request->input('challengeTitle');
    $challengeContent = $request->input('challengeContent');
    $challengeCategories = json_decode($request->input('challengeCategories'));
    $startDate = $request->input('startDate');
    $endDate = $request->input('endDate');
    $status = 'Approved';

    $challengeSlug = strtolower(trim(preg_replace('/[^A-Za-z0-9-]+/', '-', $challengeTitle)));
    $slugCheck = Challenge::where('challengeSlug', $challengeSlug)->first();
    if(!empty($slugCheck))
    {
      $str = str_random(4);
      $challengeSlug = $challengeSlug.'-'.$str;
    }

    $imageFile = 'challenge';
    if (!is_dir($imageFile)) {
      mkdir($imageFile,0777,true);
    }

    $imageName = str_random(4);
    if($challengeImage->getClientSize() > 5242880)
    {
      return Response::json(['error' => 'This image is too large.']);
    }
    if($challengeImage->getClientMimeType() != "image/png" && $challengeImage->getClientMimeType() != "image/jpeg" && $challengeImage->getClientMimeType() != "image/gif")
    {
      return Response::json(['error' => 'Not a valid PNG/JPG/GIF image.']);
    }
    $ext = $challengeImage->getClientOriginalExtension();
    $challengeImage->move($imageFile, $imageName.'.'.$ext);
    $challengeImage = $imageFile.'/'.$imageName.'.'.$ext;
    $img = Image::make($challengeImage);
    list($width, $height) = getimagesize($challengeImage);
    if($width > 512)
    {
      $img->resize(512, null, function ($constraint) {
          $constraint->aspectRatio();
      });
      if($height > 512)
      {
        $img->crop(512, 512);
      }
    }
    $img->save($challengeImage);

    $challenge = new Challenge;
    $challenge->spaceID = $spaceID;
    $challenge->challengeImage = $request->root().'/'.$challengeImage;
    $challenge->challengeTitle = $challengeTitle;
    $challenge->challengeSlug = $challengeSlug;
    $challenge->challengeContent = $challengeContent;
    $challenge->startDate = $startDate;
    $challenge->endDate = $endDate;
    $challenge->status = $status;
    $challenge->save();

    foreach($challengeCategories as $key => $category)
    {
      $cbind = new Cbind;
      $cbind->challengeID = $challenge->id;
      $cbind->categoryID = $category->value;
      $cbind->save();
    }

    return Response::json(['challenge' => $challenge->id]);
  }

  public function uploadFile(Request $request)
  {
    $rules = [
      'challengeID' => 'required',
      'challengeFile' => 'required',
    ];

    $validator = Validator::make(Purifier::clean($request->all()), $rules);
    if($validator->fails())
    {
      return Response::json(['error' => 'Please fill out all fields.']);
    }

    $challengeID = $request->input('challengeID');
    $challengeFile = $request->file('challengeFile');

    $user = Auth::user();
    $challenge = Challenge::find($challengeID);

    if($challenge->spaceID != $user->spaceID) {
      return Response::json(['error' => 'You do not have permission to do this.']);
    }

    $challengeSlug = $challenge->challengeSlug;

    if (!is_dir("uploads/".$challengeSlug)) {
      mkdir("uploads/".$challengeSlug,0777,true);
    }

    $fileName = $challengeFile->getClientOriginalName();
    $challengeFile->move("uploads/".$challengeSlug, $fileName);

    $upload = new Upload;
    $upload->challengeID = $challenge->id;
    $upload->fileData = $request->root()."/uploads/".$challengeSlug.'/'.$fileName;
    $upload->fileName = $fileName;
    $upload->save();

    return Response::json(['success' => 'File Uploaded.']);
  }

  public function show($id)
  {
    $challenge = Challenge::where('challenges.challengeSlug', $id)->where('challenges.status', 'Approved')->join('workspaces', 'challenges.spaceID', '=', 'workspaces.id')
    ->select(
      'challenges.id',
      'challenges.challengeImage',
      'challenges.challengeTitle',
      'challenges.challengeContent',
      'challenges.challengeSlug',
      'challenges.spaceID',
      'challenges.startDate',
      'challenges.endDate',
      'workspaces.logo',
      'workspaces.name',
      'workspaces.city'
    )
    ->first();

    $categories = Cbind::where('cbinds.challengeID', $challenge->id)->join('categories', 'cbinds.categoryID', '=', 'categories.id')
      ->select(
        'categories.id',
        'categories.categorySlug',
        'categories.categoryName',
        'categories.categoryColor',
        'categories.categoryTextColor'
      )
      ->get();

    $challenge->categories = $categories;

    $uploads = Upload::where('challengeID', $challenge->id)->get();

    $teams = Ptbind::where('ptbinds.challengeID', $challenge->id)->join('users', 'ptbinds.userID', '=', 'users.id')->select('users.id', 'users.avatar', 'users.name')->inRandomOrder()->take(10)->get();

    return Response::json(['challenge' => $challenge, 'uploads' => $uploads, 'teams' => $teams]);
  }

  public function showTeams($id)
  {
    $teams = Ptbind::where('ptbinds.challengeID', $id)->join('users', 'ptbinds.userID', '=', 'users.id')
      ->select(
      'users.avatar',
      'users.name'
      )
      ->paginate(15);

    return Response::json(['teams' => $teams]);
  }

  public function joinChallenge($id)
  {
    $user = Auth::user();
    /*$profile = Profile::where('userID', $user->id)->first();
    $team = Team::where('profileID', $profile->id)->first();
    if(empty($team))
    {
      return Response::json(['error' => 'You are not a Team Leader.']);
    }
    */

    $bindCheck = Ptbind::where('userID', $user->id)->where('challengeID', $id)->first();
    if(!empty($bindCheck))
    {
      return Response::json(['error' => 'You are already part of this Challenge.']);
    }

    $ptbind = new Ptbind;
    $ptbind->userID = $user->id;
    $ptbind->challengeID = $id;
    $ptbind->save();

    return Response::json(['success' => 'Challenge Joined!']);
  }

  /*public function updateStatus($id, $type)
  {
    $user = Auth::user();
    $profile = Profile::where('userID', $user->id)->first();

    $challenge = Challenge::where('profileID', $profile->id)->where('id', $id)->first();

    if($type == 'Open') {
      $challenge->status = 'Open';
    }
    else if($type == 'Close') {
      $challenge->status = 'Closed';
    }

    return Response::json(['success' => 'Challenge Updated.']);
  }*/

  /*public function setWinner($id, $uid)
  {
    $user = Auth::user();
    $profile = Profile::where('userID', $user->id)->first();

    $challenge = Challenge::where('profileID', $profile->id)->where('id', $id)->first();

  }
  */

  /*public function getPending()
  {
    $user = Auth::user();
    $profile = Profile::where('userID', $user->id)->first();
    if($profile->roleID != 1)
    {
      return Response::json(['error' => 'You do not have permission.']);
    }

    $challenges = Challenge::whereDate('challenges.startDate', '<=', date('Y-m-d'))->where('challenges.status', 'Pending')->join('profiles', 'challenges.profileID', '=', 'profiles.id')
    ->select(
      'challenges.id',
      'challenges.challengeImage',
      'challenges.challengeTitle',
      'challenges.challengeContent',
      'challenges.challengeSlug',
      'challenges.profileID',
      'challenges.startDate',
      'challenges.endDate',
      'profiles.avatar',
      'profiles.profileName',
      'profiles.profileTitle'
    )
    ->orderBy('created_at', 'DESC')
    ->paginate($count);

    foreach($challenges as $key => $challenge)
    {
      $categories = Cbind::where('cbinds.challengeID', $challenge->id)->join('categories', 'cbinds.categoryID', '=', 'categories.id')
        ->select(
          'categories.id',
          'categories.categorySlug',
          'categories.categoryName',
          'categories.categoryColor',
          'categories.categoryTextColor'
        )
        ->get();

      $challenge->categories = $categories;
    }

    return Response::json(['challenges' => $challenges]);
  }*/
  /*
  public function approve($id)
  {
    $user = Auth::user();
    $profile = Profile::where('userID', $user->id)->first();
    if($profile->roleID != 1)
    {
      return Response::json(['error' => 'You do not have permission.']);
    }

    $challenge::find($id);
    $challenge->status = "Approved";

    return Response::json(['success' => 'Challenge Approved.']);
  }
*/
  public function search(Request $request)
  {
    $rules = [
      'searchContent' => 'required'
    ];

    $validator = Validator::make(Purifier::clean($request->all()), $rules);
    if($validator->fails())
    {
      return Response::json(['error' => 'Please fill out all fields.']);
    }

    $searchContent = $request->input('searchContent');

    $challenges = Challenge::where('challenges.status', 'Approved')
    ->where('challenges.challengeTitle', 'LIKE', '%'.$searchContent.'%')
    ->orWhere('challenges.challengeContent', 'LIKE', '%'.$searchContent.'%')
    ->join('workspaces', 'challenges.spaceID', '=', 'workspaces.id')
    ->select(
      'challenges.id',
      'challenges.challengeImage',
      'challenges.challengeTitle',
      'challenges.challengeContent',
      'challenges.challengeSlug',
      'challenges.spaceID',
      'challenges.startDate',
      'challenges.endDate',
      'workspaces.avatar',
      'workspaces.name',
      'workspaces.city'
    )
    ->orderBy('challenges.created_at', 'DESC')
    ->get();

    foreach($challenges as $key => $challenge)
    {
      $categories = Cbind::where('cbinds.challengeID', $challenge->id)->join('categories', 'cbinds.categoryID', '=', 'categories.id')
        ->select(
          'categories.id',
          'categories.categorySlug',
          'categories.categoryName',
          'categories.categoryColor',
          'categories.categoryTextColor'
        )
        ->get();

      $challenge->categories = $categories;
      $challenge->challengeContent = substr(strip_tags($challenge->challengeContent), 0, 200);

    }

    return Response::json(['challenges' => $challenges]);

  }
}
