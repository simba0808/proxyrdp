<?php

namespace App\Http\Controllers\Orders;

use Illuminate\Http\Request;
use App\Http\Controllers\Controller;
use App\Http\Controllers\ClientController;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\RateLimiter;
use App\Models\User;
use Inertia\Inertia;
use Inertia\Response;

class OrderController extends Controller
{
	private $serverParameters = [
		'memory'	=>	[
			'1'		=>	'2',
			'2'		=>	'4',
			'3' 	=>	'6',
			'4'		=>	'8',
			'5'		=>	'10',
			'6' 	=>	'12',
			'7'		=>	'16',
			'8'		=>	'20',
			'9'		=>	'24',
			'10'	=>	'32'
		],
		'storage'	=>	[
			'1'		=>	'50',
			'2'		=>	'100',
			'3'		=>	'150',
			'4'		=>	'200',
			'5'		=>	'400',
			'6'		=>	'500'
		]
	];

	/**
     * Convert client request data into genuine data conversions
     */
	public function fixOrderServerParameters(array $param) : array
	{
		if (\array_key_exists('memory', $param) && \array_key_exists($param['memory'], $this->serverParameters['memory']))
			$param['memory'] = 	$this->serverParameters['memory'][$param['memory']];

		if (\array_key_exists('storage', $param) && \array_key_exists($param['storage'], $this->serverParameters['storage']))
			$param['storage'] = $this->serverParameters['storage'][$param['storage']];

		return $param;
	}


	/**
     * Calculate the price for user's server hardware choice
     */
	private function calculateServerPrice(array $param) : float
	{
		$subtotal = (float)0.00;

		if (\count($param) !== 4 || !\array_key_exists('cores', $param) || !\array_key_exists('memory', $param) || !\array_key_exists('storage', $param) || !\array_key_exists('ipv4', $param))
			return $subtotal;
		
		switch(\intval($param['cores'])) {
			case 2:
				$subtotal += 5.5;
				break;
			
			case 4:
				$subtotal += 11;
				break;
			
			case 6:
				$subtotal += 17.5;
				break;
			
			case 8:
				$subtotal += 25;
				break;
			
			case 10:
				$subtotal += 32;
				break;
			
			case 12:
				$subtotal += 38.5;
				break;

			default:
				$subtotal += 38.5;
		}

		switch(\intval($param['memory'])) {
			case 2:
				$subtotal += 3;
				break;
			
			case 4:
				$subtotal += 6;
				break;
			
			case 6:
				$subtotal += 9;
				break;
			
			case 8:
				$subtotal += 12;
				break;
			
			case 10:
				$subtotal += 15;
				break;
			
			case 12:
				$subtotal += 18;
				break;
			
			case 16:
				$subtotal += 18;
				break;
			
			case 20:
				$subtotal += 29.5;
				break;
			
			case 24:
				$subtotal += 35.5;
				break;
			
			case 32:
				$subtotal += 47.5;
				break;

			default:
				$subtotal += 47.5;
		}

		switch(\intval($param['storage'])) {
			case 50:
				$subtotal += 3.5;
				break;
			
			case 100:
				$subtotal += 7;
				break;
			
			case 150:
				$subtotal += 11;
				break;
			
			case 200:
				$subtotal += 14.5;
				break;
			
			case 400:
				$subtotal += 29;
				break;
			
			case 500:
				$subtotal += 36;
				break;
		}

		$subtotal += \intval($param['ipv4']) * 2;

		return $subtotal;
	}

	/**
     * Handle an incoming checkout request for RDPs.
     */
	public function server(Request $request)
	{
		$validator = Validator::make(request()->all(), [
			'cores' => ['required', 'in:2,4,6,8,10,12'],
			'memory' => ['required', 'in:1,2,3,4,5,6,7,8,9,10'],
			'storage' => ['required', 'in:1,2,3,4,5'],
			'ipv4' => ['required', 'in:0,1,2,3,4']
        ]);

		if ($validator->fails()) {
			return redirect()->back()->withErrors($validator->errors());
        }

		$validator = $validator->validated();

		$executed = RateLimiter::attempt(
			'create-order:' . auth()->id(),
			$perMinute = 2,
			function() use ($validator) {
				$data = $this->fixOrderServerParameters($validator);
				$data['subtotal'] = $this->calculateServerPrice($data);
				dd($data);
			}
		);

		if (!$executed) {
			return redirect()->back()->withErrors([
				'cores'	=>	'You’ve reached the rate limit for this resource, try again in a moment please!'
			], 'servers');
		}
	}
}