<?php

namespace Tests\Unit;

use App\Support\PhpUploadLimits;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use PHPUnit\Framework\TestCase;

class PhpUploadLimitsTest extends TestCase
{
    public function test_parse_size_handles_megabytes(): void
    {
        $this->assertSame(8 * 1024 * 1024, PhpUploadLimits::parseSize('8M'));
    }

    public function test_parse_size_handles_gigabytes(): void
    {
        $this->assertSame(1024 * 1024 * 1024, PhpUploadLimits::parseSize('1G'));
    }

    public function test_parse_size_handles_kilobytes(): void
    {
        $this->assertSame(512 * 1024, PhpUploadLimits::parseSize('512K'));
    }

    public function test_parse_size_handles_lowercase_unit_suffix(): void
    {
        $this->assertSame(2 * 1024 * 1024, PhpUploadLimits::parseSize('2m'));
    }

    public function test_parse_size_handles_a_bare_number_as_bytes(): void
    {
        $this->assertSame(100, PhpUploadLimits::parseSize('100'));
    }

    /** php.ini "-1" i "0" oznaczają "bez limitu" dla tych dwóch dyrektyw. */
    public function test_parse_size_treats_unlimited_values_as_max_int(): void
    {
        $this->assertSame(PHP_INT_MAX, PhpUploadLimits::parseSize('-1'));
        $this->assertSame(PHP_INT_MAX, PhpUploadLimits::parseSize('0'));
        $this->assertSame(PHP_INT_MAX, PhpUploadLimits::parseSize(''));
    }

    public function test_request_was_truncated_when_content_length_is_set_but_both_bags_are_empty(): void
    {
        $request = Request::create('/x', 'POST', [], [], [], ['CONTENT_LENGTH' => '99999999']);

        $this->assertTrue(PhpUploadLimits::requestWasTruncated($request));
    }

    public function test_request_was_not_truncated_when_post_data_is_present(): void
    {
        $request = Request::create('/x', 'POST', ['checksum' => 'abc'], [], [], ['CONTENT_LENGTH' => '100']);

        $this->assertFalse(PhpUploadLimits::requestWasTruncated($request));
    }

    public function test_request_was_not_truncated_when_content_length_is_zero(): void
    {
        $request = Request::create('/x', 'POST', [], [], [], ['CONTENT_LENGTH' => '0']);

        $this->assertFalse(PhpUploadLimits::requestWasTruncated($request));
    }

    public function test_request_was_not_truncated_when_a_file_actually_arrived(): void
    {
        $file = UploadedFile::fake()->create('update.zip', 10);
        $request = Request::create('/x', 'POST', [], [], ['package' => $file], ['CONTENT_LENGTH' => '10240']);

        $this->assertFalse(PhpUploadLimits::requestWasTruncated($request));
    }
}
