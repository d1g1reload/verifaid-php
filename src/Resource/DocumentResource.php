<?php

declare(strict_types=1);

namespace Verifaid\Resource;

use SplFileInfo;
use Verifaid\Client;
use Verifaid\Exception\ApiException;
use Verifaid\Exception\ConnectionException;
use Verifaid\Exception\InvalidArgumentException;
use Verifaid\Image;

/**
 * Ekstraksi data dari lima jenis dokumen identitas Indonesia.
 *
 * Field yang tidak ada atau tidak terbaca pada dokumen dikembalikan sebagai
 * string kosong, bukan dihilangkan.
 */
abstract class DocumentResource
{
    protected Client $client;

    public function __construct(Client $client)
    {
        $this->client = $client;
    }

    /**
     * Prefix path endpoint, misalnya "ocr" atau "h2h".
     */
    abstract protected function prefix(): string;

    /**
     * Ekstrak data Kartu Tanda Penduduk (e-KTP).
     *
     * @param string|SplFileInfo|Image $image
     *
     * @return array{
     *     nik: string,
     *     full_name: string,
     *     birth_place_date: string,
     *     gender: string,
     *     blood_type: string,
     *     address_ktp: string,
     *     rt_rw: string,
     *     village: string,
     *     subdistrict: string,
     *     city: string,
     *     province: string,
     *     religion: string,
     *     marital_status: string,
     *     occupation: string,
     *     nationality: string,
     *     valid_until: string
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function ktp($image): array
    {
        return $this->extract('ktp', $image);
    }

    /**
     * Ekstrak data Surat Izin Mengemudi (SIM).
     *
     * @param string|SplFileInfo|Image $image
     *
     * @return array{
     *     license_type: string,
     *     license_number: string,
     *     full_name: string,
     *     birth_place_date: string,
     *     blood_type: string,
     *     gender: string,
     *     address_sim: string,
     *     occupation: string,
     *     issued_by: string,
     *     valid_until: string
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function sim($image): array
    {
        return $this->extract('sim', $image);
    }

    /**
     * Ekstrak data kartu NPWP.
     *
     * @param string|SplFileInfo|Image $image
     *
     * @return array{
     *     npwp_number: string,
     *     full_name: string,
     *     nik: string,
     *     address_npwp: string,
     *     kpp_name: string
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function npwp($image): array
    {
        return $this->extract('npwp', $image);
    }

    /**
     * Ekstrak data kartu BPJS Kesehatan / KIS.
     *
     * @param string|SplFileInfo|Image $image
     *
     * @return array{
     *     card_number: string,
     *     full_name: string,
     *     address_bpjs: string,
     *     birth_date: string,
     *     nik: string,
     *     faskes_tingkat_1: string
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function bpjs($image): array
    {
        return $this->extract('bpjs', $image);
    }

    /**
     * Ekstrak data Kartu Keluarga (KK), termasuk seluruh anggota keluarga.
     *
     * @param string|SplFileInfo|Image $image
     *
     * @return array{
     *     informasi_keluarga: array{
     *         nomor_kk: string,
     *         kepala_keluarga: string,
     *         alamat: string,
     *         rt_rw: string,
     *         desa_kelurahan: string,
     *         kecamatan: string,
     *         kabupaten_kota: string,
     *         provinsi: string,
     *         kode_pos: string
     *     },
     *     anggota_keluarga: list<array{
     *         nama_lengkap: string,
     *         nik: string,
     *         jenis_kelamin: string,
     *         tempat_lahir: string,
     *         tanggal_lahir: string,
     *         agama: string,
     *         pendidikan: string,
     *         pekerjaan: string,
     *         status_pernikahan: string,
     *         status_hubungan: string,
     *         nama_ayah: string,
     *         nama_ibu: string
     *     }>
     * }
     *
     * @throws InvalidArgumentException|ApiException|ConnectionException
     */
    public function kk($image): array
    {
        return $this->extract('kk', $image);
    }

    /**
     * @param string|SplFileInfo|Image $image
     *
     * @return array<string, mixed>
     */
    private function extract(string $document, $image): array
    {
        return $this->client
            ->request('POST', $this->prefix() . '/' . $document, null, ['image' => Image::from($image)])
            ->getData();
    }
}
