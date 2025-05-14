<?php

    declare (strict_types = 1);

    namespace Coco\weDecode;

    class WeDecoder
    {
        //https://blog.csdn.net/chinabinlang/article/details/11797587?
        static public array $fileHeaderMap = [
            "jpg" => [
                'ff',
                'd8',
                'ff',
            ],

            "png" => [
                '89',
                '50',
                '4e',
            ],

            "bmp" => [
                '42',
                '4d',
            ],

            "gif" => [
                '47',
                '49',
                '46',
            ],
        ];

        public mixed $fp = null;

        public mixed $_file = null;

        public ?string $ext = null;

        public mixed $key = null;

        protected function __construct(string $sourceImagePath)
        {
            if (!is_file($sourceImagePath))
            {
                throw new \Exception('no file');
            }

            $this->_file = $sourceImagePath;
            $this->fp    = fopen($this->_file, 'rb');

            $this->initKey();
        }

        public static function decode(string $sourceImagePath, string $targetPath, string $fileName): bool
        {
            $obj = new static($sourceImagePath);

            return $obj->decodeImage($targetPath, $fileName);
        }

        protected function decodeImage(string $targetPath, string $fileName): bool
        {
            $file = $this->xorDecode();

            if (!is_null($file))
            {
                $saveName = rtrim($targetPath, '/') . DIRECTORY_SEPARATOR . $fileName . '.' . $this->ext;

                return static::putToFile($saveName, $file);
            }
            else
            {
                return false;
            }
        }

        protected function fileSize(): bool|int
        {
            return filesize($this->_file);
        }

        protected function close(): static
        {
            fclose($this->fp);

            return $this;
        }

        protected function fseek(int $index): static
        {
            fseek($this->fp, $index);

            return $this;
        }

        protected function eachChar(callable $callback): array
        {
            return $this->readChar($this->fileSize(), $callback);
        }

        protected function readChar(int $count = 1, callable $callback = null): array
        {
            $res = [];
            $fp  = $this->fp;

            for ($h = 0, $h_len = $count; $h < $h_len; $h++)
            {
                $t = fgetc($fp);
                if (is_callable($callback))
                {
                    $t = $callback($t);
                }
                $res[] = $t;
            }

            return $res;
        }

        protected function readCharToHex($count = 1): array
        {
            return $this->readChar($count, function($t) {
                return bin2hex($t);
            });
        }

        protected function initKey()
        {
            foreach (static::$fileHeaderMap as $k => $v)
            {
                $this->fseek(0);

                $keyArr = $this->readChar(1, function($t) use ($v) {
                    $t = $t ^ hex2bin($v[0]);

                    return bin2hex((string)$t);
                });

                //空文件
                if (!isset($keyArr[0]))
                {
                    return null;
                }

                $key = $keyArr[0];

                $this->fseek(1);

                $result = $this->readChar(1, function($t) use ($key) {
                    $t = $t ^ hex2bin($key);

                    return bin2hex((string)$t);
                });

                if ($result[0] == $v[1])
                {
                    $this->ext = $k;
                    $this->key = $key;
                    break;
                }
            }
        }

        protected function xorDecode(): ?string
        {
            if (!is_null($this->key))
            {
                $this->fseek(0);

                $result = $this->eachChar(function($t) {
                    return $t ^ hex2bin($this->key);
                });

                return implode('', $result);
            }

            $this->close();

            return null;
        }

        protected static function putToFile(string $filePath, $data, $buffSize = 4096, $mode = 'wb'): bool
        {
            if (!is_writable(dirname($filePath)))
            {
                return false;
            }

            $len = strlen($data);

            $fp = @fopen($filePath, $mode);

            flock($fp, LOCK_EX);

            $page      = 0;
            $totalPage = ceil($len / $buffSize);
            flock($fp, LOCK_UN);

            while ($page < $totalPage)
            {
                $buff = substr($data, $page * $buffSize, $buffSize);
                $page++;
                fwrite($fp, $buff);
            }

            fclose($fp);

            return true;
        }

    }
