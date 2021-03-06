<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;

use App\Http\Requests;
use App\Http\Controllers\Controller;

use App\Adjust;
use App\Variables;

class AdjustController extends Controller
{
    protected function monitor_core($from_secord, $to_secord) {
        $stepNum = Variables::getStepNum();
        $period_secord = $from_secord - $to_secord;
        $step_secord = (int)($period_secord / $stepNum);
        $now = time();
        #_tp means time point.
        $beforebefore_tp = $now - $from_secord;
        $before_tp = $now - $to_secord; 

        $adjustdt = Adjust::period($beforebefore_tp, $before_tp);
        $res = Variables::chartjs_line_one_inited_with_time($beforebefore_tp, $step_secord, $stepNum);
        foreach ($adjustdt as $adj) {
            $updated = $adj->updated;
            #$updated = $adj['updated'];
            $i = (int) (($updated - $beforebefore_tp - 10) / $step_secord);
            if ($i == $stepNum) {
                $i -= 1;
            }
            $res['datasets'][0]['data'][$i] += 1;
        }
        $res = json_encode($res);
        $url = action('AdjustController@mstep', ['']);
        return compact('res', 'from_secord', 'to_secord', 'url');
    }

    public function monitor() {
        $res = AdjustController::monitor_core(3600 * 60, 0);
        $res['title'] = "Adjust Data Monitor";
        return view('one_l_ft', $res);
    }

    public function mstep($from_to) {
        $args = explode("-", $from_to);
        $from_secord = (int)($args[0]) * 3600;
        $to_secord = (int)($args[1]) * 3600;
        $res = AdjustController::monitor_core($from_secord, $to_secord);
        return view('one_l_ft_js', $res);
    }
}
