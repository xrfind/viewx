<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Proxy;
use App\ProxyS;
use App\Variables;

class ProxyController extends Controller
{
    protected function monitor_core($from_secord, $to_secord) {
        #$period_secord > 3600s, 1h.
        #$stepNum for now 60, never > 100.
        #so, $step_secord > 36s.
        $stepNum = Variables::getStepNum();
        $period_secord = $from_secord - $to_secord;
        $step_secord = (int)($period_secord / $stepNum);
        $now = time();
        #_tp means time point.
        $beforebefore_tp = $now - $from_secord;
        $before_tp = $now - $to_secord; 
        $proxies = Proxy::period($beforebefore_tp, $before_tp);
        $pm = Variables::chartjs_line_three_inited_with_time($beforebefore_tp, $step_secord, $stepNum, 'begin', 'success', 'end');
        $codes = array();
        $codescount = 1;
        foreach ($proxies as $proxy) {
            $proxy_tp = $proxy['time'];
            $code = $proxy['code'];
            if (isset($codes[(string)$code])) {
                $codes[(string)$code] += 1;
                $codescount += 1;
            }
            else {
                $codes[(string)$code] = 1;
                $codescount += 1;
            }
            $i = (int) (($proxy_tp - $beforebefore_tp) / $step_secord);
            if ($i == $stepNum) { $i -= 1; }
            if ($code == -1) {
                $pm['datasets'][0]['data'][$i] += 1;
            }
            elseif ($code == 0) {
                $pm['datasets'][1]['data'][$i] += 1;
            }
            else {
                $pm['datasets'][2]['data'][$i] += 1;
            }
        }
        $pm = json_encode($pm);

        $pe = Variables::chartjs_bar_one();
        unset($codes['-1']);
        arsort($codes);
        $paes = Variables::get('paerror');
        $paes = json_decode($paes->value, True, 3);
        foreach ($codes as $kind => $count) {
            $pe['labels'][] = $paes[$kind] . '(' . number_format($count/$codescount * 100, 1) . '%)';
            $pe['datasets'][0]['data'][] = $count;
        }
        $pe = json_encode($pe);
        $url = action('ProxyController@mstep', ['']);
        return compact('pm', 'pe', 'from_secord', 'to_secord', 'url');
    }

    public function monitor() {
        $res = ProxyController::monitor_core(60 * 3600, 0); 
        return view('proxy.monitor', $res);
    }

    public function mstep($from_to) {
        $args = explode("-", $from_to);
        $from_secord = (int)($args[0]) * 3600;
        $to_secord = (int)($args[1]) * 3600;
        $res = ProxyController::monitor_core($from_secord, $to_secord); 
        return view('proxy.mjs', $res);
    }

    public function circle_core($from_secord, $to_secord) {
        $stepNum = Variables::getStepNum();

        $period_secord = $from_secord - $to_secord;
        $step_secord = (int)($period_secord / $stepNum);
        $now = time();
        #_tp means time point.
        $beforebefore_tp = $now - $from_secord;
        $before_tp = $now - $to_secord; 

        $slist = ProxyS::period($beforebefore_tp, $before_tp);
        $res = Variables::chartjs_line_three_inited_with_time($beforebefore_tp, $step_secord, $stepNum, 'pachong', 'hidemyass', 'freeproxylists');
        foreach ($slist as $sone) {
            $source = $sone['source'];
            $count = $sone['count'];
            $time = $sone['time'];

            $i = (int) (($time - $beforebefore_tp - 10) / $step_secord);
            if ($source == 10) {
                $res['datasets'][0]['data'][$i] += $count;
            }
            elseif ($source == 9) {
                $res['datasets'][1]['data'][$i] += $count;
            }
            elseif ($source == 8)  {
                $res['datasets'][2]['data'][$i] += $count;
            }
        }
        $res = json_encode($res);
        $url = action('ProxyController@cstep', ['']);
        return compact('res', 'from_secord', 'to_secord', 'url');
    }

    public function circle() {
        $res = ProxyController::circle_core(3600 * 60, 0);
        return view('proxy.circle', $res);
    }

    public function cstep($from_to) {
        $args = explode("-", $from_to);
        $from_secord = (int)($args[0]) * 3600;
        $to_secord = (int)($args[1]) * 3600;
        $res = ProxyController::circle_core($from_secord, $to_secord); 
        return view('proxy.cjs', $res);
    }

    protected function timetox($time, $count) {
        if ($time <= 600) {
            return ceil($time/60);
        }
        if ($time <= 3600) {
            return 9 + ceil($time/600);
        }
        if ($time <= 24 * 3600) {
            return 13 + ceil($time/1800);
        }
        return $count;
    }

    protected function wtime_core($from_secord, $to_secord) {
        $stepNum = Variables::getStepNum();
        $period_secord = $from_secord - $to_secord;
        $step_secord = (int)($period_secord / $stepNum);
        $now = time();
        #_tp means time point.
        $beforebefore_tp = $now - $from_secord;
        $before_tp = $now - $to_secord; 

        $source_kind = array(8, 9, 10);
        $source_time = array();
        $source_usage_rate = array(
            'success' => array(),
            'all' => array(),
        );
        $source_usage_rate_exact = array(
            'success' => array(),
            'all' => array(),
        );
        foreach ($source_kind as $sk) {
            $source_usage_rate['success'][$sk] = 0;
            $source_usage_rate['all'][$sk] = 0;
            $source_usage_rate_exact['success'][$sk] = 0;
            $source_usage_rate_exact['all'][$sk] = 0;
            $source_time[$sk] = array();
        }
        $codedist = array(
            300 => array(),
            3600 => array(),
            14400 => array(),
            'MORE' => array(),
        );

        $ipportset = array();
        $codeset = array();
        $slist = Proxy::period($beforebefore_tp, $before_tp);
        foreach ($slist as $proxy) {
            $ipport = $proxy['ipv4_port'];
            $time = $proxy['time'];
            $code = $proxy['code'];
            $source = $proxy['source'];
            if ($code == -1) {
                $source_usage_rate['all'][$source] += 1;
            }
            elseif ($code == 0) {
                $source_usage_rate['success'][$source] += 1;
            }

            if (isset($ipportset[$ipport])) {
                if ($code == -1) {
                    unset($ipportset[$ipport]);
                    unset($codeset[$ipport]);
                    $source_usage_rate_exact['all'][$source] += 1;
                } 
                elseif ($code == 0) {
                    $keep =  $ipportset[$ipport] - $time;
                    $source_time[$source][] = $keep;
                    $source_usage_rate_exact['success'][$source] += 1;
                    if ($keep <= 300) {
                        $keep = 300;
                    }
                    elseif ($keep <= 3600) {
                        $keep = 3600;
                    }
                    elseif ($keep <= 14400) {
                        $keep = 14400;
                    }
                    else {
                        $keep = 'MORE';
                    }
                    if (isset($codedist[$keep][$codeset[$ipport]])) {
                        $codedist[$keep][$codeset[$ipport]] += 1;
                    }
                    else {
                        $codedist[$keep][$codeset[$ipport]] = 1;
                    }
                }
                else {
                    var_dump($ipport);
                    return redirect('/');
                }
            }
            else {
                if ($code == -1 || $code == 0) {
                    continue;
                }
                else{
                    $ipportset[$ipport] = $time;
                    $codeset[$ipport] = $code;
                }
            }
        }

        $x = array(0,
            60, 120, 180, 240, 300, 360, 420, 480, 540, 600, #0-9
            1200, 1800, 2400, 3000, 3600, #10-14
            5400, 7200, #15, 16
            9000, 10800, 12600, 14400, 16200, 18000, 19800, 21600, 23400, 25200, #17-26
            27000, 28800, 30600, 32400, 34200, 36000, 37800, 39600, 41400, 43200, #27-36
            45000, 46800, 48600, 50400, 52200, 54000, 55800, 57600, 59400, 61200, #37-46
            63000, 64800, 66600, 68400, 70200, 72000, 73800, 75600, 77400, 79200, #47-56
            81000, 82800, 84600, 86400 #57-60
        );


        $res = Variables::chartjs_line_three('freeproxylists', 'hidemyass', 'pachong');
        foreach ($x as $key => $time) {
            $hour = (int)($time/3600);
            $minute = (int)($time%3600/60);
            $secord = (int)($time%60);
            if ($hour == 0) { $hour = ''; }
            else { $hour = (string)$hour . 'H'; }
            if ($minute == 0) { $minute = ''; }
            else { $minute = (string)$minute . 'M';}
            if ($secord == 0) { $secord = ''; }
            else {$secord = (string)$secord . 'S';}
            $time = $hour.$minute.$secord;
            $res['labels'][$key] = $time;
            $res['datasets'][0]['data'][$key] = 0;
            $res['datasets'][1]['data'][$key] = 0;
            $res['datasets'][2]['data'][$key] = 0;
        }
        $res['labels'][count($x)] = 'MORE';
        $res['datasets'][0]['data'][count($x)] = 0;
        $res['datasets'][1]['data'][count($x)] = 0;
        $res['datasets'][2]['data'][count($x)] = 0;
        foreach ($source_time as $source => $times) {
            foreach ($times as $time) {
                $id = ProxyController::timetox($time, count($x));
                $res['datasets'][$source - 8]['data'][$id] += 1;
            }
        }
        $res = json_encode($res);

        $code_res_a = array();
        $paes = Variables::get('paerror');
        $paes = json_decode($paes->value, True, 3);
        foreach ($codedist as $time => $codes) {
            $rest = Variables::chartjs_line_three('a', 'a', 'b');
            arsort($codes);
            foreach ($codes as $code => $count) {
                $rest['labels'][] = $paes[$code];
                $rest['datasets'][0]['data'][] = $count;
            }
            $code_res_a[$time] = json_encode($rest);
        }

        return compact('res', 'code_res_a', 'step_secord', 'stepNum', 'source_usage_rate', 'source_usage_rate_exact');
    }

    public function health() {
        $res = ProxyController::wtime_core(3600 * 60, 0);
        return view('proxy.health', $res);
    }

    public function hstep($step) {
        $res = ProxyController::wtime_core($step);
        return view('proxy.hjs', $res);
    }

    public function errortype() {
        $paes = Variables::get('paerror');
        $paes = json_decode($paes->value, True, 3);
        ksort($paes);
        return view('proxy.errortype', compact('paes'));
    }

}
