<?php

namespace service\event;

use app\Rabbit;
use db\Mysql;
use db\SqlMapper;
use service\config\UpgradeUserThreshold;
use service\UserPlan;

class UpgradeVip
{
    /**
     * @param $message {"task": 1, "user": 1,  "user_plan": 0, "event": "upgrade_vip"}
     */
    function handling($message)
    {
        $db = Mysql::instance()->get();
        $db->begin();

        $taskId = $message->task;
        $userId = $message->user;

        $vip = UserPlan::VIP;
        $sql = [
            "update user set plan=$vip where id=$userId",
            "update user_chain set user_plan=$vip where user=$userId",
            "update user_chain set mentor_plan=$vip where mentor=$userId",
            "update task set status=1 where id=$taskId",
        ];

        $db->exec($sql);

        logging("user $userId upgrade to vip");

        // trigger upgrade_partner or not
        $threshold = new UpgradeUserThreshold();
        $chain = new SqlMapper('user_chain');
        $chain->load(['user=? and length=1', $userId]);
        if (!$chain->dry() && $chain['mentor_plan'] == $vip) {
            $parentId = $chain['mentor'];
            $count = $chain->count(['mentor=? and user_plan>=?', $parentId, $vip]);
            if ($count >= $threshold->get(UpgradeUserThreshold::VIP_2_PARTNER)) {
                $event = 'upgrade_partner';
                $data = [
                    'event' => $event,
                    'user' => $parentId,
                    'user_plan' => $vip,
                ];
                $task = new SqlMapper('task');
                $task['name'] = $event;
                $task['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
                $task->save();
                $upgradeMessage = json_encode(array_merge(['task' => $task['id']], $data), JSON_UNESCAPED_UNICODE);
                logging("[event][$event][$upgradeMessage]");
                Rabbit::send('peachExchange', $upgradeMessage);
            } else {
                logging("parent $parentId child-vip count: $count");
            }
        }

        $db->commit();
    }
}
