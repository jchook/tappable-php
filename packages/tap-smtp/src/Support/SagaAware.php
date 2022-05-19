<?php

trait SagaAware
{
	private function runSaga($saga)
	{
		while ($saga->valid()) {
			$effect = $saga->current();

			if ($effect instanceof Generator) {

			}

			$saga->send($effect());
		}
	}
}