# Change log
All notable changes to this project will be documented in this file.

The format is based on [Keep a Changelog](http://keepachangelog.com/)
and this project adheres to [Semantic Versioning](http://semver.org/).

## [[*next-version*]] - YYYY-MM-DD
### Changed
- Timezone object creation is now outsourced by a trait from the `rebelcode/time-abstract` package.

## [0.1-alpha14] - 2018-12-12
### Fixed
- The download-hiding query was causing Downloads created using v0.2 or later to not be shown in the list.

## [0.1-alpha13] - 2018-12-05
### Changed
- Replaced `session_lengths` with `session_types`.
- Session type labels are used as price names, if they are given.

### Fixed
- The download-hiding query was affecting other pages, such as the Payment History and Customer pages.
- The status counts on the Download page were including services.

## [0.1-alpha12] - 2018-10-30
### Added
- New services entity manager replaces the CQRS resource models.
- Service now includes the ID of the image.
- Service now includes a color value for admin display purposes.
- New handler that hides services from the Downloads list.

### Changed
- Removed the services CQRS resource models.

## [0.1-alpha11] - 2018-10-08
### Fixed
- WP Query arg extraction from expressions always assumed a POST ID query.
- The state-provision and update handlers can now query and save for non-published downloads.

## [0.1-alpha10] - 2018-09-24
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
