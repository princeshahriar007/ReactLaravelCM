<?php

namespace App\Http\Controllers\User;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use App\Contact;
use Validator;
use Illuminate\Routing\UrlGenerator;

class ContactController extends Controller
{
    protected $contacts;
    protected $base_url;

    public function __construct(UrlGenerator $urlGenerator)
    {
        $this->middleware("Auth::users");
        $this->contacts = new Contact;
        $this->base_url = $urlGenerator->to('/');
    }

    //this function/endpoint is to create a new contact specific to a user
    public function addContacts(Request $request){
        $validator = Validator::make($request->all(),
        [
            'token'=>'required',
            'firstname'=>'required|string',
            'phonenumber'=>'required|string',
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>$validator->messages()->toArray(),
            ], 500);
        }

        $profile_picture = $request->profile_image;
        $file_name = "";
        if($profile_picture==null){
            $file_name = "default-avatar.png";
        }else{
            $generate_name= uniqid()."_".time().date("Ymd")."_IMG";
            $base64Image = $profile_picture;
            $fileBin = file_get_contents($base64Image);
            $mimetype = mime_content_type($fileBin);
            if("image/png"==$mimetype){
                $file_name = $generate_name."png";
            }else if("image/jpg"==$mimetype){
                $file_name = $generate_name."jpg";
            }else if("image/jpeg"==$mimetype){
                $file_name = $generate_name."jpeg";
            }else{
                return response()->json([
                    "success"=>false,
                    "message"=>"Only png, jpg and jpeg formats are supported for setting profile pictures"
                ], 500);
            }
        }

        $user_token = $request->token;
        $user = auth("users")->authenticate($user_token);
        $user_id = $user->id;

        $this->contacts->user_id = $user_id;
        $this->contacts->phonenumber = $request->phonenumber;
        $this->contacts->firstname = $request->firstname;
        $this->contacts->lastname = $request->lastname;
        $this->contacts->email = $request->email;
        $this->contacts->image_file = $request->profile_image;
        $this->contacts->save();
        if($profile_picture == null){

        }else{
            file_put_contents("./profile_images/".$file_name, $fileBin);
        }

        return response()->json([
            "success"=>true,
            "message"=>"contact saved successfully"
        ], 200);
    }

    //getting contacts specific to a particular user
    public function gettingPaginatedData($token, $pagination = null){
        $file_directory = $this->base_url."/profile_images";
        $user = auth("users")->authenticate($token);
        $user_id = $user->user_id;
        if($pagination == null || $pagination == ""){
            $contacts = $this->contacts->where("user_id", $user_id)->orderBy("id", "DESC")->get()->toArray();

            return response()->json([
                "success"=>true,
                "data"=>$contacts,
                "file_directory"=>$file_directory
            ], 200);
        }

        $contacts_paginated = $this->contacts->where("user_id", $user_id)->orderBy("id", "DESC")->paginate($pagination);
        return response()->json([
            "success"=>true,
            "data"=>$contacts_paginated,
            "file_directory"=>$file_directory
        ], 200);
    }

    //update contact endpoint/function
    public function editSingleData(Request $request, $id){

        $validator = Validator::make($request->all(),
        [
            "firstname"=>"required|string",
            "phonenumber"=>"required|string",
        ]);

        if($validator->fails()){
            return response()->json([
                'success'=>false,
                'message'=>$validator->messages()->toArray(),
            ], 500);
        }

        $findData = $this->contacts::find($id);
        if(!$findData){
            return response()->json([
                'success'=>false,
                'message'=>"This contact has no valid id",
            ], 500);
        }

        $getFile = $findData->image_file;
        $getFile=="default-avatar.png"? :unlink("./profile_images/".$getFile);


        $profile_picture = $request->profile_image;
        $file_name = "";
        if($profile_picture==null){
            $file_name = "default-avatar.png";
        }else{
            $generate_name= uniqid()."_".time().date("Ymd")."_IMG";
            $base64Image = $profile_picture;
            $fileBin = file_get_contents($base64Image);
            $mimetype = mime_content_type($fileBin);
            if("image/png"==$mimetype){
                $file_name = $generate_name."png";
            }else if("image/jpg"==$mimetype){
                $file_name = $generate_name."jpg";
            }else if("image/jpeg"==$mimetype){
                $file_name = $generate_name."jpeg";
            }else{
                return response()->json([
                    "success"=>false,
                    "message"=>"Only png, jpg and jpeg formats are supported for setting profile pictures"
                ], 500);
            }

            $findData->firstname = $request->firstname;
            $findData->phonenumber = $request->phonenumber;
            $findData->image_file = $request->image_file;
            $findData->lastname = $request->lastname;
            $findData->email = $request->email;
            $findData->save();

            if($profile_picture == null){

            }else{
                file_put_contents("./profile_images/".$file_name, $fileBin);
            }

            return response()->json([
                "success"=>true,
                "message"=>"contact updated successfully"
            ], 200);

        }
    }

    public function deleteContacts($id){
        $findData = $this->contacts::find($id);
        if(!$findData){
            return response()->json([
                "success"=>true,
                "message"=>"contact with this id does not exist"
            ], 500);
        }

        $getFile = $findData->image_file;
        if($findData->delete()){
            $getFile == "default-avatar.png"? :unlink("./profile_images/".$getFile);
        }

        return response()->json([
            "success"=>true,
            "message"=>"contact deleted succesfully"
        ], 200);
    }

    public function getSingleData($id){
        $file_directory = $this->base_url."/profiel_images";
        $findData = $this->contacts::find($id);
        if(!$findData){
            return response()->json([
                "success"=>true,
                "message"=>"contact with this id does not exist"
            ], 500);
        }

        return response()->json([
            "success"=>true,
            "data"=>$findData,
            "file_directory"=>$file_directory
        ], 200);
    }

    //this function is to search for data as well as paginating our data searched
    public function searchData($search, $token, $pagination=null){
        $file_directory = $this->base_url."/profiel_images";
        $user = auth("users")->authenticate($token);
        $user_id = $user->id;
        if($pagination==null || $pagination==""){
            $non_paginated_search_query = $this->contacts::where("user_id", $user_id)->where(function($query) use ($search){
                $query->where("firstname", "LIKE", "%$search%")->orWhere("lastname", "LIKE", "%$search%")->
                orWhere("email", "LIKE", "%$search%")->orWhere("phonenumber", "LIKE", "%$search%");
            })->orderBy("id", "DESC")->get()->toArray();
            return response()->json([
                "success"=>true,
                "data"=>$non_paginated_search_query,
                "file_directory"=>$file_directory
            ], 200);
        }

        $paginated_search_query = $this->contacts::where("user_id", $user_id)->where(function($query) use ($search){
            $query->where("firstname", "LIKE", "%$search%")->orWhere("lastname", "LIKE", "%$search%")->
            orWhere("email", "LIKE", "%$search%")->orWhere("phonenumber", "LIKE", "%$search%");
        })->orderBy("id", "DESC")->paginate($pagination);
        return response()->json([
            "success"=>true,
            "data"=>$paginated_search_query,
            "file_directory"=>$file_directory
        ], 200);
    }
}
