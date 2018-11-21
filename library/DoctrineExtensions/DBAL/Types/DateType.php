<?php

namespace DoctrineExtensions\DBAL\Types;

use Doctrine\DBAL\Platforms\AbstractPlatform, 
        Doctrine\DBAL\Types\Type;

/**
 * Type that maps an SQL DATE to a PHP Date object.
 *
 * @since 2.0
 */
class DateType extends Type {

    public function getName()
    {
        return Type::DATE;
    }

    public function getSQLDeclaration(array $fieldDeclaration, AbstractPlatform $platform)
    {
        return $platform->getDateTypeDeclarationSQL($fieldDeclaration);
    }

    public function convertToDatabaseValue($value, AbstractPlatform $platform)
    {
        return ($value !== null) ? $value->format($platform->getDateFormatString()) : null;
    }

    public function convertToPHPValue($value, AbstractPlatform $platform)
    {
        if ($value === null) {
            return null;
        }

        if (strstr($platform->getDateFormatString(), ' 00:00:00') && !strstr($value, '00:00:00'))
            $value = substr($value, 0, 10) . ' 00:00:00';

        $val = \DateTime::createFromFormat('!' . $platform->getDateFormatString(), $value);
        
        if (!$val) {
            throw ConversionException::conversionFailedFormat($value, $this->getName(), $platform->getDateFormatString());
        }
        return $val;
    }

}