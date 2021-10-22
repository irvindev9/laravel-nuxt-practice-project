<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\Link;

class LinkController extends Controller
{
    public function index($id){
        return Link::where("user_id", $id)->get();
    }
}
