<?php
/** 只解析旧$para数组字面量，不include/eval旧站PHP。拒绝函数、变量引用及插值字符串。 */
final class CustomFormLegacyFields
{
    private array $tokens = [];
    private int $at = 0;
    public function parse(string $source, string $encoding = "GB18030"): array
    {
        $this->tokens = [];
        $this->at = 0;
        if (strlen($source) > 1048576) {
            throw new InvalidArgumentException("字段文件过大");
        }
        $source = mb_convert_encoding($source, "UTF-8", $encoding);
        foreach (token_get_all($source) as $token) {
            if (
                is_array($token) &&
                in_array(
                    $token[0],
                    [T_OPEN_TAG, T_CLOSE_TAG, T_WHITESPACE, T_COMMENT, T_DOC_COMMENT],
                    true
                )
            ) {
                continue;
            }
            $this->tokens[] = $token;
        }
        $first = $this->tokens[$this->at++] ?? null;
        if (
            !is_array($first) ||
            !($first[0] === T_RETURN || ($first[0] === T_VARIABLE && $first[1] === '$para'))
        ) {
            throw new InvalidArgumentException('需要旧$para定义');
        }
        if ($first[0] === T_VARIABLE && ($this->tokens[$this->at++] ?? null) !== "=") {
            throw new InvalidArgumentException("无效定义");
        }
        $value = $this->value(0);
        if (($this->tokens[$this->at] ?? null) === ";") {
            $this->at++;
        }
        if ($this->at !== count($this->tokens) || !is_array($value)) {
            throw new InvalidArgumentException("文件包含非字段定义代码");
        }
        return CustomFormFields::definition(["name" => "导入字段", "fields" => $value])["fields"];
    }
    private function value(int $depth): mixed
    {
        if ($depth > 12) {
            throw new InvalidArgumentException("字段嵌套过深");
        }
        $t = $this->tokens[$this->at++] ?? null;
        if ($t === "[" || (is_array($t) && $t[0] === T_ARRAY)) {
            $end = $t === "[" ? "]" : ")";
            if ($end === ")" && ($this->tokens[$this->at++] ?? null) !== "(") {
                throw new InvalidArgumentException("数组格式错误");
            }
            $out = [];
            while (($this->tokens[$this->at] ?? null) !== $end) {
                $v = $this->value($depth + 1);
                $arrow = $this->tokens[$this->at] ?? null;
                if (is_array($arrow) && $arrow[0] === T_DOUBLE_ARROW) {
                    $this->at++;
                    if (!is_int($v) && !is_string($v)) {
                        throw new InvalidArgumentException("数组键无效");
                    }
                    if (array_key_exists($v, $out)) {
                        throw new InvalidArgumentException("重复字段");
                    }
                    $out[$v] = $this->value($depth + 1);
                } else {
                    $out[] = $v;
                }
                if (($this->tokens[$this->at] ?? null) === $end) {
                    break;
                }
                if (($this->tokens[$this->at++] ?? null) !== ",") {
                    throw new InvalidArgumentException("数组分隔符无效");
                }
            }
            $this->at++;
            return $out;
        }
        if (is_array($t) && $t[0] === T_CONSTANT_ENCAPSED_STRING) {
            $s = substr($t[1], 1, -1);
            return $t[1][0] === "'"
                ? str_replace(["\\'", "\\\\"], ["'", "\\"], $s)
                : stripcslashes($s);
        }
        if (is_array($t) && $t[0] === T_LNUMBER && ctype_digit($t[1])) {
            return (int) $t[1];
        }
        if (is_array($t) && $t[0] === T_STRING) {
            return match (strtolower($t[1])) {
                "true" => true,
                "false" => false,
                "null" => null,
                default => throw new InvalidArgumentException("禁止执行旧脚本")
            };
        }
        throw new InvalidArgumentException("只接受数组、字面量文字、整数和布尔值");
    }
}
