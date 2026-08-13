<?php
/**
 *   _ _ _     _____  _              __   __
 *  | (_) |   |  __ \| |             \ \ / /
 *  | |_| |__ | |__) | |__  _   _ ___ \ V /
 *  | | | '_ \|  ___/| '_ \| | | / __| > <
 *  | | | |_) | |    | | | | |_| \__ \/ . \
 *  |_|_|_.__/|_|    |_| |_|\__, |___/_/ \_\
 *                           __/ |
 *                          |___/
 *
 * Copyright (C) 2016-2026 NetherGames Network
 *
 * This is private software, you cannot redistribute and/or modify it in any way
 * unless given explicit permission to do so. If you have not been given explicit
 * permission to view or modify this software you should take the appropriate actions
 * to remove this software from your device immediately.
 *
 * libPhysX was meant to be a stripped version of Nvidia's PhysX Physics Engine, developed purely
 * for the use cases of NetherGames and its systems. In no way shape or form is libPhysX related
 * to Nvidia's PhysX except for the logical understanding between the systems.
 *
 * @author Shaheryar Sohail
 *
 */
declare(strict_types=1);

namespace libPhysX;

use libPhysX\internal\RayInterference;
use libPhysX\internal\Rotation;
use libPhysX\utility\ConversionX;
use pocketmine\math\Vector3;
use RuntimeException;

/**
 * Class PhysX
 * @package libPhysX
 */
class PhysX
{

    public const MOTION = 0;
    public const ROTATION = 1;

    public const DEFAULT_ROTATION_PRECISION = 20.0;

    public const RELATIVE_DIRECTION_FORWARD = 0;
    public const RELATIVE_DIRECTION_BACKWARD = 1;
    public const RELATIVE_DIRECTION_RIGHT = 2;
    public const RELATIVE_DIRECTION_LEFT = 3;
    public const RELATIVE_DIRECTION_UP = 4;
    public const RELATIVE_DIRECTION_DOWN = 5;

    public const GRAVITY = 0.08;
    public const DRAG = 0.98;

    /** @var float */
    private static float $globalMicroTime = 0.0;

    /**
     * Compares two rotations at certain angular precisions.
     *
     * Returns true if they're similar.
     * Returns false if they're not.
     *
     * @param Rotation $rotationOne
     * @param Rotation $rotationTwo
     * @param float $precisionYaw
     * @param float $precisionPitch
     * @return bool
     */
    public static function compareRotation(Rotation $rotationOne, Rotation $rotationTwo, float $precisionYaw = self::DEFAULT_ROTATION_PRECISION, float $precisionPitch = self::DEFAULT_ROTATION_PRECISION): bool
    {
        $yawOne = $rotationOne->yaw;
        $yawTwo = $rotationTwo->yaw;
        $yawDelta = abs($yawOne - $yawTwo);
        $yawCheck = false;
        if ($yawDelta <= $precisionYaw) {
            $yawCheck = true;
        }
        $pitchOne = $rotationOne->pitch;
        $pitchTwo = $rotationTwo->pitch;
        $pitchDelta = abs($pitchOne - $pitchTwo);
        $pitchCheck = false;
        if ($pitchDelta <= $precisionPitch) {
            $pitchCheck = true;
        }
        return $yawCheck === true && $pitchCheck === true;
    }

    /**
     * Calculates Motion and Rotation Physics given parameters.
     *
     * @param Vector3 $current
     * @param Vector3 $target
     * @param float $speed
     * @param bool $ignoreUpward
     * @param bool $invertRotation
     * @param float $movementOffset
     * @return array
     */
    public static function calculateMRPhysic(Vector3 $current, Vector3 $target, float $speed = 0.25, bool $ignoreUpward = true, bool $invertRotation = false, float $movementOffset = 0.7): array
    {
        $motionVector = self::calculateMotionVector($current, $target, $speed, $ignoreUpward, $movementOffset);
        $rotation = self::calculateRotationEulerAngle($current, $target, $invertRotation);
        return [
            self::MOTION => $motionVector,
            self::ROTATION => $rotation
        ];
    }

    /**
     * Calculates a motion vector given the parameters.
     *
     * @param Vector3 $current
     * @param Vector3 $target
     * @param float $speed
     * @param bool $ignoreUpward
     * @param float $offset
     * @return Vector3
     */
    public static function calculateMotionVector(Vector3 $current, Vector3 $target, float $speed = 0.25, bool $ignoreUpward = true, float $offset = 0.7): Vector3
    {
        $x = $target->x - $current->x;
        $y = $target->y - $current->y;
        $z = $target->z - $current->z;
        $squared = ($x ** 2) + ($z ** 2);
        $magnitude = sqrt($squared);
        $motionVector = new Vector3(0, 0, 0);
        if ($squared >= $offset && $magnitude !== 0.0) {
            $xComponent = $x / $magnitude;
            $motionVector->x = $speed * $xComponent;
            $zComponent = $z / $magnitude;
            $motionVector->z = $speed * $zComponent;
        }
        if ($ignoreUpward === false && (float)$y !== 0.0) {
            $motionVector->y = $speed * $y;
        }
        return $motionVector;
    }

    /**
     * Calculates the rotational euler angles given the parameters.
     *
     * @param Vector3 $current
     * @param Vector3 $target
     * @param bool $invertRotation
     * @return Rotation
     */
    public static function calculateRotationEulerAngle(Vector3 $current, Vector3 $target, bool $invertRotation = false): Rotation
    {
        $x = $target->x - $current->x;
        $y = $target->y - $current->y;
        $z = $target->z - $current->z;
        $squared = ($x ** 2) + ($z ** 2);
        $magnitude = sqrt($squared);
        $arcTangentXZ = atan2(-$x, $z);
        $yaw = rad2deg($arcTangentXZ);
        if ($invertRotation === true) {
            $yaw += 180;
        }
        $arcTangentY = -atan2($y, $magnitude);
        $pitch = rad2deg($arcTangentY);
        return new Rotation($yaw, $pitch);
    }

    /**
     * Calculates unit vectors around a given origin vector.
     * The includeCorner parameter specifies whether it should include corners or not.
     * The includeOrigin parameter specifies whether it should include the origin vector or not.
     *
     * @param Vector3 $origin
     * @param bool $includeCorner
     * @param bool $includeOrigin
     * @return Vector3[]
     */
    public static function calculateUnitVectorAroundOrigin(Vector3 $origin, bool $includeCorner = false, bool $includeOrigin = false): array
    {
        $unitVectorList = [];
        for ($x = -1; $x <= 1; $x++) {
            for ($y = -1; $y <= 1; $y++) {
                for ($z = -1; $z <= 1; $z++) {
                    if ($x === 0 && $y === 0 && $z === 0) {
                        if ($includeOrigin === true) {
                            $unitVectorList[] = $origin;
                        }
                        continue;
                    }
                    $uX = $origin->x + $x;
                    $uY = $origin->y + $y;
                    $uZ = $origin->z + $z;
                    if ($uX !== $origin->x && $uY !== $origin->y && $uZ !== $origin->z) {
                        if ($includeCorner === true) {
                            $unitVectorList[] = new Vector3($uX, $uY, $uZ);
                        }
                        continue;
                    }
                    $unitVectorList[] = new Vector3($uX, $uY, $uZ);
                }
            }
        }
        return $unitVectorList;
    }

    /**
     * Generate an area-subtraction based inverse ray trace result.
     * Performance efficient if you know what you want the ray to be blocked by.
     *
     * @param Vector3 $rayOrigin
     * @param Vector3 $rayBlock
     * @param bool $rayInterferenceDetection
     * @param int $radiusX
     * @param int $radiusY
     * @param int $radiusZ
     * @return Vector3[]
     */
    public static function inverseRayTrace(Vector3 $rayOrigin, Vector3 $rayBlock, bool $rayInterferenceDetection = true, int $radiusX = 10, int $radiusY = 10, int $radiusZ = 10): array
    {
        $unreachableList = [];
        $delta = $rayBlock->subtractVector($rayOrigin);
        $max = new Vector3($radiusX, $radiusY, $radiusZ);
        $min = new Vector3(-$radiusX, -$radiusY, -$radiusZ);
        if ($delta->x > 0) {
            $min->x = $delta->x;
        } else if ($delta->x < 0) {
            $max->x = $delta->x;
        } else {
            $min->x = $delta->x;
            $max->x = $delta->x;
        }
        if ($delta->y > 0) {
            $min->y = $delta->y;
        } else if ($delta->y < 0) {
            $max->y = $delta->y;
        } else {
            $min->y = $delta->y;
            $max->y = $delta->y;
        }
        if ($delta->z > 0) {
            $min->z = $delta->z;
        } else if ($delta->z < 0) {
            $max->z = $delta->z;
        } else {
            $min->z = $delta->z;
            $max->z = $delta->z;
        }
        for ($x = $min->x; $x <= $max->x; $x++) {
            for ($y = $min->y; $y <= $max->y; $y++) {
                for ($z = $min->z; $z <= $max->z; $z++) {
                    $unreachableList[] = new Vector3($rayOrigin->x + $x, $rayOrigin->y + $y, $rayOrigin->z + $z);
                }
            }
        }
        if ($rayInterferenceDetection === false) {
            return $unreachableList;
        }
        $rayInterference = new RayInterference($delta->x, $delta->y, $delta->z, $radiusX, $radiusY, $radiusZ);
        $rayInterferenceX = $rayInterference->getX();
        $rayInterferenceY = $rayInterference->getY();
        $rayInterferenceZ = $rayInterference->getZ();
        $rayInterferenceUnreachableList = [];
        foreach ($unreachableList as $index => $unreachable) {
            $absX = abs($unreachable->x - $rayBlock->x);
            $absY = abs($unreachable->y - $rayBlock->y);
            $absZ = abs($unreachable->z - $rayBlock->z);
            if ($absX > $rayInterferenceX || $absY > $rayInterferenceY || $absZ > $rayInterferenceZ) {
                continue;
            }
            $rayInterferenceUnreachableList[] = $unreachable;
        }
        return $rayInterferenceUnreachableList;
    }

    /**
     * Gets the relative unit-based direction vector given a specific rotation and which side to fetch.
     *
     * Throws an exception if the rotation angles are perfect corners. It's your job to make sure you
     * either catch the exception or make sure the rotation angles aren't perfect corners.
     *
     * @param Rotation $rotation
     * @param int $side
     * @return Vector3
     */
    public static function getRelativeDirectionVector(Rotation $rotation, int $side): Vector3
    {
        if ($rotation->yaw <= -0) {
            $rotation->yaw *= -1;
        }
        if ($rotation->pitch <= -0) {
            $rotation->pitch *= -1;
        }
        $directionVector = match ($side) {
            self::RELATIVE_DIRECTION_FORWARD => self::getRelativeForwardDirectionVector($rotation),
            self::RELATIVE_DIRECTION_BACKWARD => self::getRelativeBackwardDirectionVector($rotation),
            self::RELATIVE_DIRECTION_RIGHT => self::getRelativeRightDirectionVector($rotation),
            self::RELATIVE_DIRECTION_LEFT => self::getRelativeLeftDirectionVector($rotation),
            self::RELATIVE_DIRECTION_UP => self::getRelativeUpDirectionVector($rotation),
            self::RELATIVE_DIRECTION_DOWN => self::getRelativeDownDirectionVector($rotation),
            default => null,
        };
        if ($directionVector === null) {
            throw new RuntimeException('Rotation Euler Angles are at perfect corners. Unable to determine relative direction.');
        }
        return $directionVector;
    }

    /**
     * Flips the vector by 180 degrees depending on the
     * degree<float> argument provided.
     *
     * This returns the same vector if 270 <= angle <= 90.
     *
     * @param Vector3 $vector
     * @param float $degree
     * @return Vector3
     */
    public static function flipVector(Vector3 $vector, float $degree): Vector3
    {
        if ($degree > 90 && $degree < 270) {
            $vector = $vector->multiply(-1);
        }
        return $vector;
    }

    /**
     * Reflect a vector given a normal.
     *
     * This is a performance costly method.
     * Do not make excessive calls in
     * synchronized environment.
     *
     * @param Vector3 $vector
     * @param Vector3 $normal
     * @return Vector3
     */
    public static function reflectVector(Vector3 $vector, Vector3 $normal): Vector3
    {
        $vectorOnNormal = $vector->dot($normal) / $normal->dot($normal);
        $reversedNormal = $normal->multiply(2 * $vectorOnNormal);
        return $vector->subtractVector($reversedNormal);
    }

    /**
     * Simulate the Gravitational Motion.
     * This method edits the given motion vector.
     * You shouldn't expect a return as it'll edit
     * the object in memory directly. This ensures
     * maximum performance and that extra objects
     * aren't created for the motion.
     *
     * @param Vector3 $motion
     * @param bool $applyDrag
     * @return void
     */
    public static function simulateGravityMotion(Vector3 $motion, bool $applyDrag = false): void
    {
        $motion->y -= self::GRAVITY;
        if ($applyDrag) {
            $motion->y *= self::DRAG;
        }
    }

    /**
     * Starts timing the method. Used for measuring execution time.
     * Note: Calling this method also resets the time.
     *
     * @return void
     */
    public static function time(): void
    {
        self::$globalMicroTime = microtime(true);
    }

    /**
     * Get the elapsed time if time() has been called beforehand.
     * If time() hasn't been called beforehand, it returns the current machine time.
     * Return time is in milliseconds (ms).
     *
     * @return float
     */
    public static function getTime(): float
    {
        return microtime(true) - self::$globalMicroTime;
    }

    /**
     * INTERNAL USE ONLY. NOT TO BE CALLED OUTSIDE OF PhysX.
     *
     * Gets the relative unit-based forward direction vector given a specific rotation.
     * Returns null if the rotation angles are perfect corners.
     *
     * @param Rotation $rotation
     * @return Vector3|null
     */
    private static function getRelativeForwardDirectionVector(Rotation $rotation): ?Vector3
    {
        $angularVector = null;
        $yaw = $rotation->yaw;
        $specialYawOne = $yaw > ConversionX::convertRadToDegree(1.75) && $yaw <= ConversionX::convertRadToDegree(2);
        $specialYawTwo = $yaw >= 0 && $yaw < ConversionX::convertRadToDegree(0.25);
        $specialYawCondition = $specialYawOne || $specialYawTwo;
        if ($specialYawCondition === true) {
            $angularVector = new Vector3(0, 0, 1);
        }
        if ($yaw > ConversionX::convertRadToDegree(0.25) && $yaw < ConversionX::convertRadToDegree(0.75)) {
            $angularVector = new Vector3(-1, 0, 0);
        }
        if ($yaw > ConversionX::convertRadToDegree(0.75) && $yaw < ConversionX::convertRadToDegree(1.25)) {
            $angularVector = new Vector3(0, 0, -1);
        }
        if ($yaw > ConversionX::convertRadToDegree(1.25) && $yaw < ConversionX::convertRadToDegree(1.75)) {
            $angularVector = new Vector3(1, 0, 0);
        }
        if ($angularVector === null) {
            return null;
        }
        $pitch = $rotation->pitch;
        return self::flipVector($angularVector, $pitch);
    }

    /**
     * INTERNAL USE ONLY. NOT TO BE CALLED OUTSIDE OF PhysX.
     *
     * Gets the relative unit-based backward direction vector given a specific rotation.
     * Returns null if the rotation angles are perfect corners.
     *
     * @param Rotation $rotation
     * @return Vector3|null
     */
    private static function getRelativeBackwardDirectionVector(Rotation $rotation): ?Vector3
    {
        $forwardVector = self::getRelativeForwardDirectionVector($rotation);
        if ($forwardVector === null) {
            return null;
        }
        return self::flipVector($forwardVector, 180);
    }

    /**
     * INTERNAL USE ONLY. NOT TO BE CALLED OUTSIDE OF PhysX.
     *
     * Gets the relative unit-based right direction vector given a specific rotation.
     * Returns null if the rotation angles are perfect corners.
     *
     * @param Rotation $rotation
     * @return Vector3|null
     */
    private static function getRelativeRightDirectionVector(Rotation $rotation): ?Vector3
    {
        $angularVector = null;
        $yaw = $rotation->yaw;
        $specialYawOne = $yaw > ConversionX::convertRadToDegree(1.75) && $yaw <= ConversionX::convertRadToDegree(2);
        $specialYawTwo = $yaw >= 0 && $yaw < ConversionX::convertRadToDegree(0.25);
        $specialYawCondition = $specialYawOne || $specialYawTwo;
        if ($specialYawCondition === true) {
            $angularVector = new Vector3(1, 0, 0);
        }
        if ($yaw > ConversionX::convertRadToDegree(0.25) && $yaw < ConversionX::convertRadToDegree(0.75)) {
            $angularVector = new Vector3(0, 0, 1);
        }
        if ($yaw > ConversionX::convertRadToDegree(0.75) && $yaw < ConversionX::convertRadToDegree(1.25)) {
            $angularVector = new Vector3(-1, 0, 0);
        }
        if ($yaw > ConversionX::convertRadToDegree(1.25) && $yaw < ConversionX::convertRadToDegree(1.75)) {
            $angularVector = new Vector3(0, 0, -1);
        }
        if ($angularVector === null) {
            return null;
        }
        $pitch = $rotation->pitch;
        return self::flipVector($angularVector, $pitch);
    }

    /**
     * INTERNAL USE ONLY. NOT TO BE CALLED OUTSIDE OF PhysX.
     *
     * Gets the relative unit-based left direction vector given a specific rotation.
     * Returns null if the rotation angles are perfect corners.
     *
     * @param Rotation $rotation
     * @return Vector3|null
     */
    private static function getRelativeLeftDirectionVector(Rotation $rotation): ?Vector3
    {
        $rightVector = self::getRelativeRightDirectionVector($rotation);
        if ($rightVector === null) {
            return null;
        }
        return self::flipVector($rightVector, 180);
    }

    /**
     * INTERNAL USE ONLY. NOT TO BE CALLED OUTSIDE OF PhysX.
     *
     * Gets the relative unit-based up direction vector given a specific rotation.
     * Returns null if the rotation angles are perfect corners.
     *
     * @param Rotation $rotation
     * @return Vector3
     */
    private static function getRelativeUpDirectionVector(Rotation $rotation): Vector3
    {
        $angularVector = new Vector3(0, 1, 0);
        $pitch = $rotation->pitch;
        return self::flipVector($angularVector, $pitch);
    }

    /**
     * INTERNAL USE ONLY. NOT TO BE CALLED OUTSIDE OF PhysX.
     *
     * Gets the relative unit-based down direction vector given a specific rotation.
     * Returns null if the rotation angles are perfect corners.
     *
     * @param Rotation $rotation
     * @return Vector3
     */
    private static function getRelativeDownDirectionVector(Rotation $rotation): Vector3
    {
        $upVector = self::getRelativeUpDirectionVector($rotation);
        return self::flipVector($upVector, 180);
    }
}