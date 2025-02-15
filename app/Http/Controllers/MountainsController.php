<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Models\User;
use App\Models\Mountain;
class MountainsController extends Controller
{
    public function mountain(Mountain $mountain){
        return view('mountain' , ['mountain' => $mountain]);
    }
}
