<?php
$action = trim(filter_input(INPUT_GET, 'action', FILTER_SANITIZE_STRING));

$daemon_path = '/home/ctdaemon/ctdaemon';
$data = array("status_daemon"=> 0, "status"=>'', "msg"=>'');
      
//Daemon status
$status = 0;
$pid = 0;

if(file_exists($daemon_path.'/run/ctdaemon.pid')) {
    $check_pid = (int)file_get_contents($daemon_path.'/run/ctdaemon.pid');
    if($check_pid > 0) {
        //find process
        $p = (int)exec('/bin/ps -e |  awk \'{print $1}\' | awk \'/^'.$check_pid.'$/\'');
        if($p !== 0) {
            $status = 1;
            $pid = (int)$p;
        }
        else {
            unlink($daemon_path.'/run/ctdaemon.pid');
        }
    }
    else {
        unlink($daemon_path.'/run/ctdaemon.pid');
    }
}

//stop lost processes
if($status == 0) {
    //stop all over main process
    $p = (int)exec('/bin/ps -e | awk \'/\{php\} ctd_main/\' | awk \'{print $1}\'');
    if($p > 0) {
        $kill = posix_kill($p, SIGTERM);
    }
    //sleep(3);
    //find zombie and stop
    exec('/bin/ps -e | awk \'/\{php\} ctd_/\' | awk \'{print $1}\'', $ps);
    if(is_array($ps)) {
        foreach ($ps as $pse) {
            $kill = posix_kill($p, SIGKILL);
            echo '.';
        }
    }
}

switch ($action) {
    case 'start':
        if($status == 0) {
            exec('bash -c "'.$daemon_path.'/bin/ctdaemon start > /dev/null 2>&1"');
            //sleep(3);
            $check_pid = (int)file_get_contents($daemon_path.'/run/ctdaemon.pid');
            if($check_pid > 0) {
                //find process
                $p = (int)exec('/bin/ps -e |  awk \'{print $1}\' | awk \'/^'.$check_pid.'$/\'');
                if($p !== 0) {
                    $data['status_daemon'] = 1;
                    $data['status'] = 'ok';
                    $data['msg'] = 'Deamon started';
                }
                else {
                    $data['status_daemon'] = 0;
                    $data['status'] = 'error';
                    $data['msg'] = 'Failed start daemon. Process not found';
                }
            }
            else {
                $data['status_daemon'] = 0;
                $data['status'] = 'error';
                $data['msg'] = 'Failed start daemon. Pid file error';
            }
        }
        else {
            $data['status_daemon'] = 1;
            $data['status'] = 'error';
            $data['msg'] = 'Failed start daemon. Deamon already started';
        }
        break;
    case 'stop':
        if($status == 1) {
            $check_pid = (int)file_get_contents($daemon_path.'/run/ctdaemon.pid');
            exec('bash -c "'.$daemon_path.'/bin/ctdaemon stop > /dev/null 2>&1"');
            //sleep(3);
            $p = (int)exec('/bin/ps -e |  awk \'{print $1}\' | awk \'/^'.$check_pid.'$/\'');
            if($p !== 0) {
                $data['status_daemon'] = 1;
                $data['status'] = 'error';
                $data['msg'] = 'Failed stop daemon. Daemon still started';
            }
            else {
                $data['status_daemon'] = 0;
                $data['status'] = 'ok';
                $data['msg'] = 'Daemon stopped';
            }
        }
        else {
            $data['status_daemon'] = 0;
            $data['status'] = 'error';
            $data['msg'] = 'Failed stop daemon. Deamon already stopped';
        }
        break;
    case 'restart':
        if($status == 1) {
            $check_pid = (int)file_get_contents($daemon_path.'/run/ctdaemon.pid');
            exec('bash -c "'.$daemon_path.'/bin/ctdaemon stop > /dev/null 2>&1"');
            //sleep(3);
            $p = (int)exec('/bin/ps -e |  awk \'{print $1}\' | awk \'/^'.$check_pid.'$/\'');
            if($p !== 0) {
                $data['status_daemon'] = 1;
                $data['status'] = 'error';
                $data['msg'] = 'Failed stop daemon. Daemon still started';
            }
            else {
                $status = 0;
            }
        }
        if($status == 0 && $data['status'] != 'error') {
            exec('bash -c "'.$daemon_path.'/bin/ctdaemon start > /dev/null 2>&1"');
            //sleep(3);
            $check_pid = (int)file_get_contents($daemon_path.'/run/ctdaemon.pid');
            if($check_pid > 0) {
                //find process
                $p = (int)exec('/bin/ps -e |  awk \'{print $1}\' | awk \'/^'.$check_pid.'$/\'');
                if($p !== 0) {
                    $data['status_daemon'] = 1;
                    $data['status'] = 'ok';
                    $data['msg'] = 'Deamon started';
                }
                else {
                    $data['status_daemon'] = 0;
                    $data['status'] = 'error';
                    $data['msg'] = 'Failed start daemon. Process not found';
                }
            }
            else {
                $data['status_daemon'] = 0;
                $data['status'] = 'error';
                $data['msg'] = 'Failed start daemon. Pid file error';
            }
        }
        break;
    case 'status':
        if($status == 1) {
            $data['status_daemon'] = 1;
            $data['status'] = 'ok';
            $data['msg'] = 'ACTIVE';
        }
        else {
            $data['status_daemon'] = 0;
            $data['status'] = 'ok';
            $data['msg'] = 'STOPPED';
        }
        break;
    default:
        $data['status'] = 'error';
        $data['msg'] = 'Error. Undefined action';
}

echo json_encode($data, JSON_UNESCAPED_UNICODE);
exit();
?>