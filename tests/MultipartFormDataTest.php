<?php

namespace ReactphpX\MultipartFormData\Tests;

use PHPUnit\Framework\TestCase;
use ReactphpX\MultipartFormData\MultipartFormData;
use ReactphpX\MultipartFormData\FormFile;
use ReactphpX\MultipartFormData\MultiFormFile;

class MultipartFormDataTest extends TestCase
{
    private string $testDir;

    protected function setUp(): void
    {
        parent::setUp();
        $this->testDir = __DIR__;
        $this->createTestFiles();
    }

    protected function tearDown(): void
    {
        $this->cleanupTestFiles();
        parent::tearDown();
    }

    /**
     * 测试构造函数数组支持
     */
    public function testConstructorArraySupport(): void
    {
        $formData = new MultipartFormData([
            'name' => 'John',
            'tags' => ['tag1', 'tag2', 'tag3'],
            'nested' => [
                'level1' => ['item1', 'item2'],
                'level2' => 'simple_value'
            ]
        ]);

        $this->assertInstanceOf(MultipartFormData::class, $formData);
        
        $headers = $formData->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
        $this->assertStringStartsWith('multipart/form-data; boundary=', $headers['Content-Type']);
    }

    /**
     * 测试外部 addField 数组支持
     */
    public function testExternalAddFieldArraySupport(): void
    {
        $formData = new MultipartFormData();
        
        // 测试字符串字段
        $formData->addField('name', 'John');
        
        // 测试简单数组
        $formData->addField('tags', ['tag1', 'tag2', 'tag3']);
        
        // 测试嵌套数组
        $formData->addField('nested', [
            'level1' => ['item1', 'item2'],
            'level2' => 'simple_value'
        ]);

        $this->assertInstanceOf(MultipartFormData::class, $formData);
        
        $headers = $formData->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
    }

    /**
     * 测试混合字段类型
     */
    public function testMixedFieldTypes(): void
    {
        $formData = new MultipartFormData([
            'name' => 'John',
            'age' => 30,
            'tags' => ['tag1', 'tag2'],
            'file' => new FormFile($this->testDir . '/test_file.txt'),
            'nested' => [
                'key1' => 'value1',
                'key2' => ['subkey1', 'subkey2']
            ]
        ]);

        // 外部添加更多字段
        $formData->addField('external_string', 'external_value');
        $formData->addField('external_array', ['ext1', 'ext2']);

        $this->assertInstanceOf(MultipartFormData::class, $formData);
    }

    /**
     * 测试深层嵌套数组
     */
    public function testNestedArrays(): void
    {
        $deepNested = [
            'level1' => [
                'level2' => [
                    'level3' => [
                        'level4' => ['deep_value1', 'deep_value2'],
                        'level4_simple' => 'simple_deep_value'
                    ],
                    'level3_simple' => 'simple_value'
                ]
            ],
            'top_level' => 'top_value'
        ];

        $formData = new MultipartFormData(['deep_nested' => $deepNested]);
        
        $this->assertInstanceOf(MultipartFormData::class, $formData);

        // 测试外部添加深层嵌套
        $formData->addField('external_deep', [
            'a' => ['b' => ['c' => ['d' => 'deep_external_value']]]
        ]);

        $this->assertInstanceOf(MultipartFormData::class, $formData);
    }

    /**
     * 测试 http_build_query 一致性
     */
    public function testHttpBuildQueryConsistency(): void
    {
        $testData = [
            'simple' => 'value',
            'array' => ['item1', 'item2', 'item3'],
            'nested' => [
                'key1' => 'value1',
                'key2' => ['subkey1', 'subkey2']
            ]
        ];

        // 使用 http_build_query 手动处理
        $queryString = http_build_query($testData);
        $pairs = explode('&', $queryString);
        $manualParsed = [];
        
        foreach ($pairs as $pair) {
            if (empty($pair)) continue;
            $parts = explode('=', $pair, 2);
            if (count($parts) === 2) {
                $key = urldecode($parts[0]);
                $value = urldecode($parts[1]);
                $manualParsed[$key] = $value;
            }
        }

        $this->assertNotEmpty($manualParsed);
        $this->assertArrayHasKey('simple', $manualParsed);
        $this->assertArrayHasKey('array[0]', $manualParsed);
        $this->assertArrayHasKey('nested[key1]', $manualParsed);

        // 测试 FormData 使用相同逻辑
        $formData = new MultipartFormData($testData);
        $this->assertInstanceOf(MultipartFormData::class, $formData);
    }

    /**
     * 测试文件上传
     */
    public function testFileUploads(): void
    {
        $formData = new MultipartFormData();
        $formData->addFile('single_file', $this->testDir . '/test_file.txt');
        
        $headers = $formData->getHeaders();
        $this->assertStringContainsString('multipart/form-data', $headers['Content-Type']);
    }

    /**
     * 测试多文件上传
     */
    public function testMultiFileUploads(): void
    {
        $multiFile = new MultiFormFile([
            $this->testDir . '/test_file.txt',
            $this->testDir . '/test_file2.txt'
        ]);

        $this->assertEquals(2, $multiFile->getFileCount());
        
        $formData = new MultipartFormData(['multi_files' => $multiFile]);
        $this->assertInstanceOf(MultipartFormData::class, $formData);
    }

    /**
     * 测试头部信息
     */
    public function testHeaders(): void
    {
        $formData = new MultipartFormData(['test' => 'value']);
        $headers = $formData->getHeaders();
        
        $this->assertIsArray($headers);
        $this->assertArrayHasKey('Content-Type', $headers);
        
        $contentType = $headers['Content-Type'];
        $this->assertStringStartsWith('multipart/form-data; boundary=', $contentType);
        
        // 提取边界值
        preg_match('/boundary=([a-f0-9]+)/', $contentType, $matches);
        $this->assertNotEmpty($matches[1]);
        $this->assertEquals(32, strlen($matches[1]));
    }

    /**
     * 测试边界情况
     */
    public function testEdgeCases(): void
    {
        // 空数组
        $formData = new MultipartFormData(['empty_array' => []]);
        $this->assertInstanceOf(MultipartFormData::class, $formData);
        
        // 特殊字符
        $formData = new MultipartFormData([
            'special_chars' => ['value with spaces', 'value&with&ampersand', 'value=with=equals']
        ]);
        $this->assertInstanceOf(MultipartFormData::class, $formData);
        
        // 数字键
        $formData = new MultipartFormData([
            'numeric_keys' => [0 => 'zero', 1 => 'one', 5 => 'five']
        ]);
        $this->assertInstanceOf(MultipartFormData::class, $formData);
        
        // 混合键类型
        $formData = new MultipartFormData([
            'mixed_keys' => ['string_key' => 'string_value', 0 => 'numeric_value']
        ]);
        $this->assertInstanceOf(MultipartFormData::class, $formData);
    }

    /**
     * 测试空构造函数
     */
    public function testEmptyConstructor(): void
    {
        $formData = new MultipartFormData();
        $this->assertInstanceOf(MultipartFormData::class, $formData);
        
        $headers = $formData->getHeaders();
        $this->assertArrayHasKey('Content-Type', $headers);
    }

    /**
     * 测试字段覆盖
     */
    public function testFieldOverride(): void
    {
        $formData = new MultipartFormData(['test' => 'original_value']);
        $formData->addField('test', 'new_value');
        
        $this->assertInstanceOf(MultipartFormData::class, $formData);
    }

    /**
     * 测试数组字段与文件字段混合
     */
    public function testArrayFieldsWithFiles(): void
    {
        $formData = new MultipartFormData([
            'user_info' => [
                'name' => 'John',
                'tags' => ['developer', 'php']
            ],
            'profile_pic' => new FormFile($this->testDir . '/test_file.txt')
        ]);

        $formData->addField('additional_data', [
            'scores' => [100, 95, 88],
            'metadata' => 'extra_info'
        ]);

        $this->assertInstanceOf(MultipartFormData::class, $formData);
    }

    /**
     * 创建测试文件
     */
    private function createTestFiles(): void
    {
        if (!file_exists($this->testDir . '/test_file.txt')) {
            file_put_contents($this->testDir . '/test_file.txt', 'Test file content for testing.');
        }
        
        if (!file_exists($this->testDir . '/test_file2.txt')) {
            file_put_contents($this->testDir . '/test_file2.txt', 'Second test file content.');
        }
    }

    /**
     * 清理测试文件
     */
    private function cleanupTestFiles(): void
    {
        $testFiles = [
            $this->testDir . '/test_file.txt',
            $this->testDir . '/test_file2.txt'
        ];
        
        foreach ($testFiles as $file) {
            if (file_exists($file)) {
                unlink($file);
            }
        }
    }
} 