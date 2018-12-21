<?php
namespace test\db;

use app\Rabbit;
use db\Mysql;
use db\SqlMapper;
use PHPUnit\Framework\TestCase;
use Ramsey\Uuid\Uuid;
use service\config\UpgradeUserThreshold;
use service\UserPlan;

class UpgradeUserTest extends TestCase
{
    const MENTOR_ID = 1;
    const AFFILIATE_LENGTH = 6;

    public function test()
    {
        $defaultUser = 1;
        $threshold = new UpgradeUserThreshold();
        // guarantee default user upgrade from vip to partner
        for ($i = 0; $i < $threshold->get(UpgradeUserThreshold::VIP_2_PARTNER); $i++) {
            $this->trigger($defaultUser);
        }
        // guarantee default user upgrade from partner to super
        $db = Mysql::instance()->get();
        $query = $db->exec("select u.id from user u, user_chain c where u.id=c.user and c.mentor=$defaultUser order by u.id limit " . $threshold->get(UpgradeUserThreshold::PARTNER_2_SUPER));
        foreach ($query as $data) {
            for ($i = 0; $i < $threshold->get(UpgradeUserThreshold::VIP_2_PARTNER); $i++) {
                $this->trigger($data['id']);
            }
        }
        $this->assertTrue(true);
    }

    private function trigger($mentorId)
    {
        $user = new SqlMapper('user');
        $user['affiliate'] = $this->mockAffiliate();
        $user['update_time'] = date('Y-m-d H:i:s');
        $user->save();

        $mentor = new SqlMapper('user');
        $mentor->load("id=$mentorId");

        if ($mentor['plan'] == UserPlan::VIP) {
            $createUser = [
                'event' => 'create_user',
                'user' => $user['id'],
                'user_plan' => UserPlan::NORMAL,
                'mentor' => $mentor['id'],
                'mentor_plan' => $mentor['plan'],
            ];
            $task = new SqlMapper('task');
            $task['name'] = 'create_user';
            $task['data'] = json_encode($createUser, JSON_UNESCAPED_UNICODE);
            $task->save();
            $message = json_encode(array_merge(['task' => $task['id']], $createUser), JSON_UNESCAPED_UNICODE);
            logging("[event][create_user][$message]");
            Rabbit::send('peachExchange', $message);

            // mock payment for vip
            $upgradeVip = [
                'event' => 'upgrade_vip',
                'user' => $user['id'],
                'user_plan' => UserPlan::NORMAL,
            ];
            $task->reset();
            $task['name'] = 'upgrade_vip';
            $task['data'] = json_encode($createUser, JSON_UNESCAPED_UNICODE);
            $task->save();
            $message = json_encode(array_merge(['task' => $task['id']], $upgradeVip), JSON_UNESCAPED_UNICODE);
            logging("[event][upgrade_vip][$message]");
            Rabbit::send('peachExchange', $message);
        }
    }

    private function mockAffiliate() : string
    {
        $uuid = Uuid::uuid1()->toString();
        if (is_numeric($uuid[0])) {
            $affiliate = 'r' . substr($uuid, 0, self::AFFILIATE_LENGTH - 1);
        } else {
            $affiliate = substr($uuid, 0, self::AFFILIATE_LENGTH);
        }
        return $affiliate;
    }
}
