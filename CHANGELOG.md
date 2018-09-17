# Change log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [[*next-version*]] - YYYY-MM-DD
### Changed
- The Services `SELECT` CQRS resource model now queries for published Downloads only by default.

## [0.1-alpha9] - 2018-08-27
### Changed
- Availability rule `start` and `end` datetimes are no longer normalized according to the `all_day` option.

## [0.1-alpha8] - 2018-08-24
### Fixed
- Exclude dates used to lose timezone information.

## [0.1-alpha7] - 2018-08-15
### Added
- Now allowing services to have UTC offset timezones.

## [0.1-alpha6] - 2018-08-13
### Added
- When saving a service, the dates and times for its rules are normalized against the service's timezone.
- Added `ext-json` as a Composer dependency.

### Changed
- Removed the WordPress query post limit of 5 posts when querying for services without a specific limit.

## [0.1-alpha5] - 2018-08-01
### Changed
- Price option names are now human-friendly duration names.

## [0.1-alpha4] - 2018-06-12
### Changed
- Service price name will now will be provided as a *human-readable* duration.

## [0.1-alpha3] - 2018-06-06
### Added
- Download prices are now filtered to correspond to the session length prices.

## [0.1-alpha2] - 2018-06-04
### Added
- Services now include `description` and `imageSrc` fields. 

## [0.1-alpha1] - 2018-05-21
Initial version.
