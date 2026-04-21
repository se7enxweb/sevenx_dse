<?php

namespace AdminNeo;

// Coverage is used in tests and removed in compilation.
if (extension_loaded("xdebug") && file_exists(sys_get_temp_dir() . "/adminneo.coverage")) {
	function save_coverage(): void
	{
		$coverage_filename = sys_get_temp_dir() . "/adminneo.coverage";
		$coverage = unserialize(file_get_contents($coverage_filename));

		foreach (xdebug_get_code_coverage() as $filename => $lines) {
			foreach ($lines as $line => $val) {
				if (!isset($coverage[$filename][$line]) || $val > 0) {
					$coverage[$filename][$line] = $val;
				}
			}

			file_put_contents($coverage_filename, serialize($coverage));
		}
	}

	xdebug_start_code_coverage(XDEBUG_CC_UNUSED | XDEBUG_CC_DEAD_CODE);
	register_shutdown_function('AdminNeo\save_coverage');
}
