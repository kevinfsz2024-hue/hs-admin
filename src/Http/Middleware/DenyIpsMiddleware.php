<?php

namespace Dcat\Admin\Http\Middleware;

use Closure;
use Illuminate\Http\Request;

/**
 * 后台禁止访问 IP.
 *
 * 读取常规管理-系统配置的 deny_ip（每行一个，支持 * 通配符，# 开头为注释）。
 */
class DenyIpsMiddleware
{
    public function handle(Request $request, Closure $next)
    {
        $denyIps = (string) general_config('deny_ip');

        if ($denyIps !== '' && $request->ip() && $this->denied($request->ip(), $denyIps)) {
            abort(403, '您的 IP 已被禁止访问后台');
        }

        return $next($request);
    }

    protected function denied(string $ip, string $denyIps): bool
    {
        foreach (preg_split('/\r\n|\r|\n/', $denyIps) as $pattern) {
            $pattern = trim($pattern);

            if ($pattern === '' || str_starts_with($pattern, '#')) {
                continue;
            }

            $matched = str_contains($pattern, '*')
                ? fnmatch($pattern, $ip)
                : $pattern === $ip;

            if ($matched) {
                return true;
            }
        }

        return false;
    }
}
