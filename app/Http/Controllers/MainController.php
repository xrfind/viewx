<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

class MainController extends Controller
{
    public function index() {
        return view('home');
    }

    public function onlyhas() {
        return view('rxqs.onlyhas');
    }

    public function onlyin() {
        return view('rxqs.onlyin');
    }

    protected function readf() {
        $nodeids = \File::get(public_path()."/_nodeids", "File Not Found (_nodeids)");
        $nodeids = json_decode($nodeids, true);
        $edges = \File::get(public_path()."/_edges", "File Not Found (_edges)");
        $edges = json_decode($edges, true);
        return compact('nodeids', 'edges');
    }

    public function node($id) {
        if ($id == -1) {
            $res = MainController::all_core();
            return view('rxqs.js', $res);
        }
        $data = MainController::readf();
        $nodeids = $data['nodeids'];
        $edges = $data['edges'];
        $id = str_replace('-', '.', $id);
        if (!isset($nodeids[$id])) {
            foreach ($nodeids as $node => $nid) {
                if ($id == $nid) {
                    $id = $node;
                    break;
                }
            }
            if (!isset($nodeids[$id])) {
                $res = MainController::all_core();
                return view('rxqs.js', $res);
            }
        }
        $node = $id;
        $id = $nodeids[$node];

        $nodes = array();
        $nodes[$node] = "{id: $id, label: '$node', color: '#90C1FE'},";
        $relations = array();
        foreach ($edges[$node]['has'] as $obj) {
            $sign = "$node=>$obj";
            $tid = $nodeids[$obj];
            $relations[$sign] = "{from: $id, to: $tid, arrows:'to'},";
            $nodes[$obj] = "{id: $tid, label: '$obj', color: '#90C1FE'},";
        }
        foreach($edges[$node]['in'] as $obj) {
            $sign = "$obj=>$id";
            $fid = $nodeids[$obj];
            $relations[$sign] = "{from: $fid, to: $id, arrows:'to'},";
            $nodes[$obj] = "{id: $fid, label: '$obj', color: '#90C1FE'},";
        }
        $node = 1;
        return view('rxqs.js', compact('nodes', 'relations', 'node'));
    }


    protected function core($exclude, $keyword) {
        $data = MainController::readf();
        $nodeids = $data['nodeids'];
        $edges = $data['edges'];
        $nodes = array();
        foreach ($nodeids as $node => $id) {
            if (isset($exclude[$node])) continue;
            if ($keyword != "" && strpos($node, $keyword) !== False) continue;
            $nodes[] = "{id: $id, label: '$node', color: '#90C1FE'},";
        }
        $relations = array();
        foreach ($edges as $node => $inhas) {
            if (isset($exclude[$node])) continue;
            if ($keyword != "" && strpos($node, $keyword) !== False) continue;
            foreach($inhas['has'] as $obj) {
                if (isset($exclude[$obj])) continue;
                if ($keyword != "" && strpos($node, $keyword) !== False) continue;
                $sign = "$node=>$obj";
                $fid = $nodeids[$node];
                $tid = $nodeids[$obj];
                $relations[$sign] = "{from: $fid, to: $tid, arrows:'to'},";
            }
            foreach($inhas['in'] as $obj) {
                if (isset($exclude[$obj])) continue;
                if ($keyword != "" && strpos($node, $keyword) !== False) continue;
                $sign = "$obj=>$node";
                $tid = $nodeids[$node];
                $fid = $nodeids[$obj];
                $relations[$sign] = "{from: $fid, to: $tid, arrows:'to'},";
            }
        }
        $url = action('MainController@node', ['']);
        return compact('nodes', 'relations', 'url');
    }

    public function all() {
        $exclude = array(
            're' => 1, 'os' => 1, 'time' => 1, 'random' => 1, 'sys' => 1, 'json' => 1, 'threading' => 1, 'logging' => 1,
        );
        $keyword = "";
        $res = MainController::core($exclude, $keyword);
        return view('rxqs.all', $res);
    }

    public function start() {
        $exclude = array(
            'artisan' => 1,
            're' => 1, 'os' => 1, 'time' => 1, 'random' => 1, 'sys' => 1, 'json' => 1, 'threading' => 1, 'logging' => 1,
        );
        $keyword = 'art.';
        $res = MainController::core($exclude, $keyword);
        return view('rxqs.all', $res);
    }

    public function artisan() {
        $exclude = array(
            'start' => 1,
            're' => 1, 'os' => 1, 'time' => 1, 'random' => 1, 'sys' => 1, 'json' => 1, 'threading' => 1, 'logging' => 1,
        );
        $keyword = 'core.';
        $res = MainController::core($exclude, $keyword);
        return view('rxqs.all', $res);
    }

}
