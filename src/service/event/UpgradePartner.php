<?php

namespace service\event;

use app\Rabbit;
use db\Mysql;
use db\SqlMapper;
use service\config\UpgradeUserThreshold;
use service\UserPlan;

class UpgradePartner
{
    /**
     * @param $message {"task": 1, "user": 1,  "user_plan": 1, "event": "upgrade_partner"}
     */
    function handling($message)
    {
        $db = Mysql::instance()->get();
        $db->begin();

        $taskId = $message->task;
        $userId = $message->user;

        $partner = UserPlan::PARTNER;
        $sql = [
            "update user set plan=$partner where id=$userId",
            "update user_chain set user_plan=$partner where user=$userId",
            "update user_chain set mentor_plan=$partner where mentor=$userId",
            "update task set status=1 where id=$taskId",
        ];

        $db->exec($sql);

        logging("user $userId upgrade to partner");

        // trigger upgrade_super or not
        $threshold = new UpgradeUserThreshold();
        $chain = new SqlMapper('user_chain');
        $chain->load(['user=? and length=1', $userId]);
        if (!$chain->dry() && $chain['mentor_plan'] == $partner) {
            $parentId = $chain['mentor'];
            $count = $chain->count(['mentor=? and user_plan>=?', $parentId, $partner]);
            if ($count >= $threshold->get(UpgradeUserThreshold::PARTNER_2_SUPER)) {
                $event = 'upgrade_super';
                $data = [
                    'event' => $event,
                    'user' => $parentId,
                    'user_plan' => $partner,
                ];
                $task = new SqlMapper('task');
                $task['name'] = $event;
                $task['data'] = json_encode($data, JSON_UNESCAPED_UNICODE);
                $task->save();
                $upgradeMessage = json_encode(array_merge(['task' => $task['id']], $data), JSON_UNESCAPED_UNICODE);
                logging("[event][$event][$upgradeMessage]");
                Rabbit::send('peachExchange', $upgradeMessage);
            } else {
                logging("parent $parentId child-partner count: $count");
            }
        }

        $db->commit();
    }
}
