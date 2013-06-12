<?php

class Email_Logger implements Swift_Plugins_Logger
{
  public function add($entry)
  {
    Log::instance()->add(Log::DEBUG, $entry);
  }
  
  public function clear()
  {
  }
  
  public function dump()
  {
    return false;
  }
  
}
