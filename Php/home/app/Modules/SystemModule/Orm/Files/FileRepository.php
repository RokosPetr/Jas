<?php
declare(strict_types=1);

namespace App\Modules\SystemModule\Orm\Files;

use App\Core\Orm\BaseRepository;
use Nette\Http\FileUpload;
use Nette\Utils\FileSystem;
use Nette\Utils\Strings;
use Nextras\Dbal\Utils\DateTimeImmutable;

class FileRepository extends BaseRepository
{
    static function getEntityClassNames(): array
    {
        return [File::class];
    }

    public function createFile(FileUpload $fileUpload, string $path): File
    {
        $file = new File();
        $file->name = $fileUpload->getSanitizedName();
        $file->size = $fileUpload->getSize();
        $file->mimeType = $fileUpload->getContentType();
        $file->path = $path;
        $file->webPath = self::createWebPath($path);
        $fileUpload->move($file->link);
        return $this->persistAndFlush($file);
    }

    public function updateFile(File $file, FileUpload $fileUpload): File
    {
        $oldFilePath = $file->link;
        $file->name = $fileUpload->getSanitizedName();
        $file->size = $fileUpload->getSize();
        $file->mimeType = $fileUpload->getContentType();
        $file->createdAt = new DateTimeImmutable();
        $file->createdBy = $this->getModel()->getSysUser();
        FileSystem::delete($oldFilePath);
        $fileUpload->move($file->link);
        return $this->persistAndFlush($file);
    }

    public function removeFile(File $file): File
    {
        FileSystem::delete($file->link);
        return $this->removeAndFlush($file);
    }

    public function cloneFile(File $cloneFile, string $path): File
    {
        $file = new File();
        $file->name = $cloneFile->name;
        $file->size = $cloneFile->size;
        $file->mimeType = $cloneFile->mimeType;
        $file->path = $path;
        $file->webPath = FileRepository::createWebPath($file->path);
        FileSystem::copy($cloneFile->link, $file->link);
        return $this->persistAndFlush($file);
    }

    public static function createWebPath(string $path): ?string
    {
        if (!Strings::contains($path, '/www/')) {
            return null;
        }

        $pathSplit = explode('/', $path);

        do {
            $dir = array_shift($pathSplit);
        } while ($dir !== 'www');

        return '/' . implode('/', $pathSplit);
    }
}
