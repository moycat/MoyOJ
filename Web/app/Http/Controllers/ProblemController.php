<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Requests;

use ProblemCell;

class ProblemController extends Controller
{
    public function index(Request $request, $page = 1)
    {
        // Get the startID & tags
        $size = 20;
        $startID = ($page - 1) * $size;
        $filter = array_filter(explode(' ', $request->input('tag')));
        // Get the list
        $result = ProblemCell::index($size, $startID, $filter);
        $result['page'] = $page;
        $result['filter'] = $filter;

        return view('problem.list', $result);
    }

    public function show($pid)
    {
        $problem = ProblemCell::find($pid);

        return view('problem.view', ['problem'=>$problem]);
    }
}
