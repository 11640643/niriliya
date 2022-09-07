<?php

namespace C\S\User;

use C\L\Service;

class Level extends Service
{
    protected function setModel()
    {
        $this->model = new \C\M\UserLevel();
    }

    public function getLevel($uid)
    {
        $user = $this->di['s_user']->search($uid);
        $levels = $this->searchAll([], [], [], 'credit asc');
        if (empty($levels)) {
            return [
                'name' => '',
                'credit' => '',
                'apr' => 0,
                'next' => [
                    'name' => '',
                    'credit' => '',
                    'apr' => 0,
                ]
            ];
        }

        $nowLevel = array_merge($levels[0], ['next' => $levels[1]]);

        foreach ($levels as $item) {
            if ($item['credit'] > $user['credit']) {
                $nowLevel['next'] = $item;
                break;
            }
            $nowLevel = $item;
        }

        return $nowLevel;
    }

    public function getNextLevelScore($levelId)
    {
        $level = $this->search($levelId);
        $levels = $this->searchAll([], [], [], 'credit asc');
        if (empty($levels) || empty($level)) {
            return [0, 0];
        }

        $scores = [$level['credit'], 0];
        foreach ($levels as $item) {
            if ($item['credit'] > $level['credit']) {
                $scores[1] = $item['credit'];
                break;
            }
        }

        return $scores;
    }
}
