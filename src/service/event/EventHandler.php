<?php

namespace service\event;

class EventHandler
{
    function handling($message)
    {
        $event = strtolower($message->event);
        if (in_array($event, array_keys(Event::MAP))) {
            $class = __NAMESPACE__ . '\\' . Event::MAP[$event];
            $handler = new $class;
            $handler->handling($message);
        } else {
            logging("Unsupported event:" . $message->event);
            logging($message);
        }
    }
}
