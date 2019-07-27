<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;;
use Spatie\Permission\Models\Role;
use App\{ProgramCatrgories, Programs};
use DB;
use Illuminate\Support\Facades\Auth;
use App\Repositories\ProgramRepository;
use Illuminate\Support\Facades\Gate;

class ProgramController extends Controller
{
    protected $model;
    public function __construct(Programs $program)
    {
       // set the model
       $this->model = new ProgramRepository($program);
    }
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
    
        $program =Program::orderBy('program_name', 'desc')->get();
        return view('administrator.programs.create')->with([
            'program' => $program,
        ]);
    }

    public function bin()
    {
        if (auth()->user()->hasPermissionTo('Restore Program') OR 
            (Gate::allows('Administrator', auth()->user()))){
            $program= Programs::onlyTrashed()->get();
            return view('administrator.programs.recyclebin')->with([
                'program' => $program,
            ]);
        }else{
            return redirect()->back()->with("error", "You Dont Have Access To The Recycle Bin");
        } 
    }

    public function restore($program_id)
    {
        if (auth()->user()->hasPermissionTo('Restore Program') OR 
            (Gate::allows('Administrator', auth()->user()))){
            Programs::withTrashed()
            ->where('program_id', $program_id)
            ->restore();
            $categ= $this->model->show($program_id);
            $name = $categ->program_name;
            $ministers = $categ->ministers;
            $program_id = $categ->program_id;
            $cate = ProgramCatrgories::where('program_category_id', $program_category_id)->first();
            $program_name = $cate->program_name;
            
            activity()
                ->performedOn($categ)
                ->causedBy(auth()->user()->id)
                ->withProperties([
                    'program_name' => $name,
                    'ministers' => $ministers,
                    'program_id' => $program_id,
                   
                ])
            ->log('restored');
            return redirect()->back()->with([
                'success' => " You Have Restored". " ".$name. " " ." Details Successfully",
                
            ]);
        
        }else{
            return redirect()->back()->with("error", "You Dont Have Access To Restore From The Bin");
        } 
        
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        if (auth()->user()->hasPermissionTo('Add Program') OR 
            (Gate::allows('Administrator', auth()->user()))){
            $this->validate($request, [
                'program_name' => 'required|min:1|max:255|',
                'ministers' => 'required|min:1|max:255|',
                'program_category_id' => 'required|min:1|max:255|',
                'program_date' => 'required|min:1|max:255|',
                'start_time' => 'required|min:1|max:255|',
                'end_date' => 'required|min:1|max:255|',
            ]);

            $cate = ProgramCatrgories::where('program_category_id', $request->input("program_category_id"))->first();
            $program_category_name = $cate->program_category_name;

            if(Programs::where([
                "program_name"=> $request->input("program_name"),
                "program_date" => $request->input("program_date"),
                "program_category_id" => $program_category_id,
                ])->exists()){
                return redirect()->back()->with("error", $request->input("program_name").
                    "Already Exist for ". $request->input("program_date") . " In ". $program_name
                 
                );
            }
            $data =new Programs ([
                "program_name" => $request->input("program_name"),
                "ministers" => $request->input("ministers"),
                "program_category_id" => $request->input("program_category_id"),
                "program_date" => $request->input("program_date"),
                "start_time" => $request->input("start_time"),
                "end_date" => $request->input("end_date"),
            ]);
            
            if($data->save()){
                
                return redirect()->route("program.create")->with("success", "You Have Added " 
                .$request->input("program_name"). " For ". $request->input("program_date").  " Successfully");
            }else{
                return redirect()->back()->with("error", "Network Failure");
            } 
        } else{
            return redirect()->back()->with([
                "error" => "You Dont have Access To Create A Program",
            ]);
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($program_id)
    {
        if (auth()->user()->hasPermissionTo('Edit Program') OR 
            (Gate::allows('Administrator', auth()->user()))){
            $program =Programs::orderBy('program_name', 'desc')->get();
            $prog = $this->model->show($program_id); 
            return view('administrator.programs.edit')->with([
                "category" => $category,
                "prog" =>$prog,
               
            ]);
        } else{
            return redirect()->back()->with([
                'error' => "You Dont have Access To Edit A Program",
            ]);
        }
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $program_id)
    {
        if (auth()->user()->hasPermissionTo('Add Program') OR 
            (Gate::allows('Administrator', auth()->user()))){
            $this->validate($request, [
                'program_name' => 'required|min:1|max:255|',
                'ministers' => 'required|min:1|max:255|',
                'program_category_id' => 'required|min:1|max:255|',
                'program_date' => 'required|min:1|max:255|',
                'start_time' => 'required|min:1|max:255|',
                'end_date' => 'required|min:1|max:255|',
            ]);

            $data = ([
                "program" => $this->model->show($program_id),
                "program_name" => $request->input("program_name"),
                "ministers" => $request->input("ministers"),
                "program_category_id" => $request->input("program_category_id"),
                "program_date" => $request->input("program_date"),
                "start_time" => $request->input("start_time"),
                "end_date" => $request->input("end_date"),
            ]);
            
            if(($this->model->update($data, $program_id))){
                
                return redirect()->route("program.create")->with("success", "You Have Updated " 
                .$request->input("program_name"). " For ". $request->input("program_date").  " Successfully");
            }else{
                return redirect()->back()->with("error", "Network Failure");
            } 
        } else{
            return redirect()->back()->with([
                "error" => "You Dont have Access To Create A Program",
            ]);
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
    }
}
