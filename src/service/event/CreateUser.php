<?php

namespace service\event;

use db\Mysql;
use db\SqlMapper;

class CreateUser
{
    /**
     * @param $message {"task": 1, "user": 7, "user_plan": 0, "event": "create_user", "mentor": 1, "mentor_plan": 1}
     */
    function handling($message)
    {
        $db = Mysql::instance()->get();
        $db->begin();

        $taskId = $message->task;
        $userId = $message->user;
        $father = $message->mentor;
        $fatherPlan = $message->mentor_plan;

        $sql = [];
        $ancestor = new SqlMapper('user_chain');
        $ancestor->load(['user=?', $father], ['order' => 'id']);
        while (!$ancestor->dry()) {
            $mentor = $ancestor['mentor'];
            if ($mentor != 0) {
                $mentorPlan = $ancestor['mentor_plan'];
                $length = $ancestor['length'] + 1;
                $sql[] = "insert into user_chain (user,user_plan,mentor,mentor_plan,length) values ($userId,0,$mentor,$mentorPlan,$length)";
            }
            $ancestor->next();
        }
        $sql[] = "insert into user_chain (user,user_plan,mentor,mentor_plan,length) values ($userId,0,$father,$fatherPlan,1)";
        $sql[] = "update task set status=1 where id=$taskId";

        $db->exec($sql);

        logging("user $userId created");

        $db->commit();
    }
}
