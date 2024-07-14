<?php

namespace Brix\Core\Broker\Log;

enum LoggingLevel : string 
{
    case DEBUG = "debug";
    case INFO = "info";
    case NOTICE = "notice";
    case WARNING = "warning";
    case ERROR = "error";

    case SUCCESS = "success";
}
