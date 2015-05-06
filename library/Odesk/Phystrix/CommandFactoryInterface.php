<?php

namespace Odesk\Phystrix;

interface CommandFactoryInterface {

  /**
   * Instantiates and configures a command
   *
   * @param string $class
   *
   * @return Odesk\Phystrix\AbstractCommand
   */
  public function getCommand( $name );

} // CommandFactoryInterface
