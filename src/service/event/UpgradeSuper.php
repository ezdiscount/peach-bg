<?php

namespace service\event;

use db\Mysql;
use service\UserPlan;

class UpgradeSuper
{
    /**
     * @param $message {"task": 1, "user": 1,  "user_plan": 2, "event": "upgrade_super"}
     */
    function handling($message)
    {
        $db = Mysql::instance()->get();
        $db->begin();

        $taskId = $message->task;
        $userId = $message->user;

        $super = UserPlan::SUPER;
        $sql = [
            "update user set plan=$super where id=$userId",
            "update user_chain set user_plan=$super where user=$userId",
            "update user_chain set mentor_plan=$super where mentor=$userId",
            "update task set status=1 where id=$taskId",
        ];

        $db->exec($sql);

        logging("user $userId upgrade to super");

        $db->commit();
    }
}
