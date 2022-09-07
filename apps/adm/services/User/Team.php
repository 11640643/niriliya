<?php

namespace C\S\User;

use C\L\Service;

class Team extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserTeam();
    }

    public function getTeamLevel($uid)
    {
        $user = $this->di['s_user']->search($uid);
        $teamlevels = $this->searchAll([], [], [], 'id asc');
        // $user['team_id'] = 5;
        if (!$user['team_id']) {
            $nowteamLevel['now'] = [
                'id'=> "0"
            ];
            $nowteamLevel['next'] = $teamlevels[0];
        }else{
            foreach ($teamlevels as $key => $item) {
                if ($item['id'] == $user['team_id']) {
                    $nowteamLevel['now'] = $item;
                    if($user['team_id'] < 5){
                        $nowteamLevel['next'] = $teamlevels[$key+1];
                    }else{
                        $nowteamLevel['next'] = $teamlevels[$key];
                    }
                    
                }
            }
        }
        return $nowteamLevel;
    }
    public function getAllTeamLevel()
    {
        $teamlevels = $this->searchAll([], [], [], 'id asc');
        return $teamlevels;
    }
    public function getNextTeamLevelNum($levelId)
    {
        $teamlevel = $this->search($levelId);
        $teamlevels = $this->searchAll([], [], [], 'num asc');
        if (empty($teamlevels) || empty($teamlevel)) {
            return [0, 0];
        }

        $nums = [$teamlevel['num'], 0];
        foreach ($teamlevels as $item) {
            if ($item['num'] > $teamlevel['num']) {
                $scores[1] = $item['num'];
                break;
            }
        }

        return $nums;
    }
}
