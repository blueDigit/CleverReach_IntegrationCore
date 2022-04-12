# Changelog
All notable changes to this project will be documented in this file.
Procedure for releasing new version is [here](https://logeecom.atlassian.net/wiki/spaces/CR/pages/181600257/CORE+library+versioning+workflow).

The format is based on [Keep a Changelog](http://keepachangelog.com/en/1.0.0/).

## [v1.12.1](https://github.com/cleverreach/logeecore/compare/v1.12.0...v1.12.1)
 - Fix CampaignOrderSyncTask to support old serialized data structure
 - Adjusted autoconfigure response handling if request was successful
 - Fix support for PHP version 5.3

## [v1.12.0](https://github.com/cleverreach/logeecore/compare/v1.11.6...v1.12.0)
**BREAKING CHANGES**
### Added
 - Added `SingleSignOnProvider` class for creating SSO link
 - Added `OTPUtility` class for generating otp token
 - Added `Helper::getAttributeByName` method to return attribute object from list
 by provided attribute name
 - Added `Helper::removeUnsupportedAttributes` method which removes all
 global attributes from recipient that are not supported on integration.
 - Added `Proxy::uploadOrderItems`method which accepts recipient email and list
 of order items entities and updates recipient through upsertplus endpoint
### Changed
 - Modified `CampaingOrderSync` class (order items are sending through new method 
 `Proxy::uploadOrderItems`)
 - Modified `OrderItem` class (renamed existing field `orderId` to `orderItemId` 
 which is real order item id, added new field `orderId` and added `orderId` as 
 third constructor parameter)
 - Renamed `Attributes::getAttributeByName` to `Attributes::getAttributes`. This 
 method should return all available attributes from integration system.
 - Modified `AttributesSyncTask::getAllAttributes`.
 - Modified `Proxy::formatGlobalAttributesForApiCall` not to send attribute 
 if not exist on integration.
### Removed
 - methods `Proxy::uploadOrderItem` and `Proxy::checkUploadOrderItemResponse` since 
 `CampaingOrderSync` no longer use them.


## [v1.11.6](https://github.com/cleverreach/logeecore/compare/v1.11.5...v1.11.6)
### Changed
- Changed `Proxy::getAuthInfo()` method to send POST request using JSON.

## [v1.11.5](https://github.com/cleverreach/logeecore/compare/v1.11.4...v1.11.5)
### Changed
 - Adjusted support for PHP 5.3: Set protected property `CompositeTask::taskProgressMap` to be public.

## [v1.11.4](https://github.com/cleverreach/logeecore/compare/v1.11.3...v.1.11.4)
### Changed
 - Added support for PHP 5.3:
        Set protected method `CompositeTask::calculateProgress()` to be public and calling public method instead
            of protected property in `CompositeTask::attachReportProgressEvent()` callback method.
        `Task::serialize()` method now serializes empty array instead of `$this`.
 - Setting new refresh token also in config service when new access token is retrieved.

## [v1.11.3](https://github.com/cleverreach/logeecore/compare/v1.11.2...v.1.11.3)
### Changed
 - Fixed bug in `Proxy::getReceiversForDeactivation()`. Removed 'deactivated' timestamp.
 - Added Buyer special tag on order sync.
 
## [v1.11.2](https://github.com/cleverreach/logeecore/compare/v1.11.1...v.1.11.2)
### Changed
 - Method `Validatior::isFilterMatchingTheSchema` is changed to also check if provided condition for filter
 is in a list of available searchable expressions for that attribute.
 - Method `Proxy::uploadOrderItem` and `Proxy::checkUploadOrderItemResponse` changed creation of request
  and response handling due CleverReach API changes.
 - Method `CampaignOrderSync::calculateProgress` refactored calculation for report progress.
 - Method `Proxy::deactivateRecipients` now accepts list of `Recipient` entities instead of list of emails.
 - `RecipientDeactivateSyncTask` is updated to remove special tag Subscriber and to set 
 Newsletter attribute to 'no'. Proxy is updated to reflect these changes so deactivate method sets data properly.
 - Method `Proxy::getRecipient` changed to set tags on recipient as well.
 - In `RecipientSyncTask` if `RecipientService` returns empty batch, processing is skipped and next batch is taken.
 A change is that in previous implementation in this case system threw an error.
 
## [v1.11.1](https://github.com/cleverreach/logeecore/compare/v1.11.0...v1.11.1)
### Added
 - Added `Configuration::setAuthInfo` and `Configuration::getAuthInfo` methods for setting
 access token, token duration and refresh token. This simplifies working with user auth information.
 
### Changed
 - Method `Proxy::exchangeToken()` now returns `AuthInfo` object.
 - Method `Proxy::getValidAccessToken($token)` is changed not to get expiration time from
 config service if token is given as parameter. This fixes bug reported in CRHOOK-96.
 - Tasks `ExchangeAccessTokenTask` and `RefreshUserInfoTask` are update to reflect changes in 
 `Proxy` and `Configuration` classes.

## [v1.11.0](https://github.com/cleverreach/logeecore/compare/v1.10.1...v1.11.0) - 2018-11-21
**BREAKING CHANGES**
### Added
 - Added abstract `Configuration::getCrEventHandlerURL` method that will return URL of a 
       controller that will handle webhook calls.
 - Added `Configuration::getCrEventHandlerVerificationToken` method that will return generated 
       token for webhook validation.
 - Added `Configuration::getCrEventHandlerCallToken` method that will return call token that 
       CleverReach will send in webhook calls.
 - Added `Configuration::setCrEventHandlerCallToken` method for storing token that CleverReach 
       will send upon successful registration of webhook handler and in webhook calls.
 - Added `Proxy::registerEventHandler` method that will call CR API endpoint for
       registering events and will send URL and token pulled from config service.
 - Added `Proxy::deleteEventHandler` method that will send a DELETE call to endpoint that will unsubscribe 
       event handler for integration.
 - Added `Proxy::getRecipient` method that will call CR API, get receiver and return
       `CleverReach\BusinessLogic\Entity\Recipient` entity instance.
 - Added `RegisterEventHandlerTask`.
 - Added Refresh token support.
 - Added `Configuration::getRefreshToken`, `setRefreshToken`, `getAccessTokenExpirationTime`, 
       `setAccessTokenExpirationTime` and `isAccessTokenExpired` methods.
 - Added `Proxy::exchangeToken` method that exchanges valid access token for a new access and refresh tokens
 - Added `Proxy::getValidAccessToken` method that checks whether access token is expired 
        and retrieves a new access token if that is true.
 - Added `AuthInfo` object that encapsulates authentication information.
 - Added `ExchangeAccessToken` task that exchanges valid access token for new access and refresh tokens.
 
### Changed
 - Changed `InitialSyncTask`. Now `RegisterEventHandlerTask` is part of initial synchronization.
 - Changed `Proxy::getAccessToken` method to `Proxy::getAuthInfo` method that returns `AuthInfo` object.
 - Changed `RefreshUserInfoTask` to accept `AuthInfo` object in constructor instead of access token.
 - `Proxy::deactivateRecipients` and related private methods are no longer deprecated.

## [v1.10.1](https://github.com/cleverreach/logeecore/compare/v1.10.0...v1.10.1) - 2018-11-20

### Changed
 - Product search registration now deletes previous endpoint and adds new one on conflict.

## [v1.10.0](https://github.com/cleverreach/logeecore/compare/v1.9.0...v1.10.0) - 2018-11-07
### Added
 - `ConfigurationService` extended with methods for storing product search endpoint ID and 
 import statistics parameters.
 - Handle registration and deleting of product search endpoint in `Proxy` class.

### Changed
 - Fix check for deactivated recipients response
 - Added a lot of PHP doc comments to meet various coding standards.

## [v1.9.0](https://github.com/cleverreach/logeecore/compare/v1.8.0...v1.9.0) - 2018-10-08
### Added
 - Article search utility is extended with a support for bool and number attribute types
 - Added `Proxy::deleteRecipient` method that will delete recipient from CleverReach
 - Added `Proxy::getRecipientAsArray` method that will fetch recipient from CleverReach and return
   it as array

### Changed
 - `RecipientDeactivateSyncTask` is not longer deprecated. It should be used when `RecipientsService` does not 
 handle recipient deactivation.

## [v1.8.0](https://github.com/cleverreach/logeecore/compare/v1.7.4...v1.8.0) - 2018-08-15
**BREAKING CHANGES** 
### Added
 - Article Search utility
 - Added `ConfigRepositoryInterface` which should be implemented in every integration
 - Added `SpecialTag` and `SpecialTagCollection` classes and extended `Recipient` interface to have both
 
### Changed
 - Changed `Configuraton` interface to be abstract class and implemented methods that are not 
   integration specific. Those which are integration specific declared as abstract
 - `Recipients` interface now has new method `getAllSpecialTags` that has to be implemented
   in all classes implementing this interface. When implementing, add special tags by using static methods from
   `SpecialTag` class, for example `SpecialTag::customer()` will create `SpecialTag` "Customer".
 - Updated `FilterSyncTask` and `RecipientsSyncTask` to handle special tags.
 
 ## [v1.7.4](https://github.com/cleverreach/logeecore/compare/v1.7.3...v1.7.4) - 2018-08-09
### Changed
 - Fixed bug in `FilterSyncTask::deleteFilters` method
 
 ## [v1.7.3](https://github.com/cleverreach/logeecore/compare/v1.7.2...v1.7.3) - 2018-07-30
### Changed
 - Changed `Tag::__toString` method that will cut tag if its length is longer than 49 (50) characters
 
## [v1.7.2](https://github.com/cleverreach/logeecore/compare/v1.7.1...v1.7.2) - 2018-07-16
### Changed
 - Fix bug in `Tag::__toString` method (regex will not be applied if tag in old format is sent)
 
## [v1.7.1](https://github.com/cleverreach/logeecore/compare/v1.7.0...v1.7.1) - 2018-07-13
### Changed
 - Fix bug in `FilterSyncTask` regarding deleting filters.
 
## [v1.7.0](https://github.com/cleverreach/logeecore/compare/v1.6.0...v1.7.0) - 2018-07-13
**BREAKING CHANGES** 
### Added
 - `Configuration` interface has additional method `getIntegrationListName` that has to be implemented in 
 derived classes. This should return the name of the list (Group) that will be created on CleverReach side during
 initial sync. `GroupSyncTask` is updated to use this method.

### Changed
 - Class `Tag` now requires both name and type parameters and does not accept integration name parameter.
 It will throw `InvalidArgumentException` if one of the parameters is not set.
 - Removed parameter `$compareAsTitle` from `TagCollection::hasTag` method and method now uses full tag for comparison.
 - Fixed detecting filters to create / delete to use first condition instead of filter name and added 
 additional validation on delete to remove only filters created by integration (bug fix).
 
### Removed
 - Methods `Tag::setIntegrationName` and `TagCollection::setIntegrationName` are removed and CORE is now handling
 setting tag prefix. Prefix will be added only if tag Type is set so backward compatibility with tags in old format
 is kept.
 
## [v1.6.0](https://github.com/cleverreach/logeecore/compare/v1.5.2...v1.6.0) - 2018-07-05
**BREAKING CHANGES** 
### Added
 - Added `DeletedPrefixedFilterSyncTask` that will delete all segments in old format (PREF-G-Name)
 - Added `UpdateTagsToNewSystemTask` that will synchronize tags in new format and old ones
 - Added `Tag` and `TagCollection` classes to handle tag and support filter manipulation

### Changed
 - Updated all classes that were using recipient tags and segment names to use new classes for tag manipulation. 
 Classes updated:
   - `RecipientDTO` - `tagsForDelete` is now `TagCollection`
   - `Recipient` (entity) - `tags` is now `TagCollection`
   - `Recipients` service interface - updated PHPdoc and `getTags` method should return `TagCollection`
   - `RecipientSyncTask` now expects `$additionalTagsToDelete` to be `TagCollection` and all methods are updated to 
   handle it that way. Also, previous array `stateData` is replaced with proper properties to make code more readable 
   and maintainable.
   - `FilterSyncTask` now works with `TagCollection` and is updated to reflect changes in other classes.
   - `Proxy` also utilizes the usage of `TagCollection` now.
 - Updated tests to support this change and added tests for new classes
 - Updated `Task::reportProgress` method to receive float (instead base points)
 - Changed access level of `Task::percentToBasePoint`s method from `protected` to `private` since it should be used only 
 in class `Task`.
 - Updated reporting progress in all sync tasks 

### Removed 
 - Removed method `CleverReach\Infrastructure\Interfaces\Required\Configuration::getInstanceTagPrefix()` and this
 should not be used in any classes implementing this interface as it is not needed anymore.
 

## [v1.5.2](https://github.com/cleverreach/logeecore/compare/v1.5.1...v1.5.2) - 2018-06-13
### Added
 - Added `Proxy::getBaseUrl` for getting CleverReach API base url.
 - Added `Proxy::getAuthenticationUrl` for getting CleverReach API auth url.
 - Added `Proxy::getToken for getting` CleverReach API token url.
 
### Changed
 - Methods `Proxy::call`, `Proxy::getAccessToken` and `Proxy::getAuthUrl` now use get methods for fetching url 
of using constants.

## [v1.5.1](https://github.com/cleverreach/logeecore/compare/v1.5.0...v1.5.1) - 2018-06-12
### Added
- Added unit test for autoconfigure feature.
- Added `HttpResponse::isSuccessful` method which checks response status.

### Changed
- Renaming `HttpClient::makeTest` to `HttpClient::isRequestSuccessful`.

## [v1.5.0](https://github.com/cleverreach/logeecore/compare/v1.4.0...v1.5.0) - 2018-06-04
### Changed
- **BREAKING CHANGE:** Proxy interface call method is extended by parameter $accessToken.
Please check in your integration if your override Proxy class for this change.

### Added
- Method `HttpClient::autoConfigure` for checking server configuration. This method should be called and validated
in integration.
- Method `HttpClient::getAdditionalOptions` which needs to be overridden in integration in order to return all
 possible combinations for additional curl options.
- Method `HttpClient::setAdditionalOptions` which needs to be overridden in integration in order to save combination
to some persisted array which `HttpClient` can use it later while creating request.
- Method `HttpClient::resetAdditionalOptions` which needs to be overridden in integration in order to reset
 to its default values persisted array which `HttpClient` uses later while creating request.

## [v1.4.0](https://github.com/cleverreach/logeecore/compare/v1.3.2...v1.4.0) - 2018-05-24   
### Added
-  New task type is added: `CompositeTask`. This abstract class represents a task that is made out of other tasks and 
execution represents sequential execution of composed tasks. Each task has its percentage of progress and 
`CompositeTask` takes care of overall progress. Tasks definitions are given through constructor. Each task is 
represented by its key (name) that can be an arbitrary string. Method `createSubTask($taskKey)` has to be 
implemented in derived class and this is the place where actual task is created based on its key.

### Changed
- Changed `InitialSyncTask` to extend `CompositeTask` and all related unit tests are refactored to reflect this change.
- **BREAKING CHANGE:** `InitialSyncTask::getProgressByTask()` now returns task progress grouped by 3 main 
task groups. Please check method's docs for more details.
- Base `Task` class now has new static method `::getClassName()` that removes a need for previous 
constant `CLASS_NAME` in each task class. This new method will return the name of the called class without namespace.
For example, calling `\CleverReach\BusinessLogic\Sync\FilterSyncTask::getClassName` will return `FilterSyncTask` string.
Instance method `Task::getType()` internally calls static method `getClassName()` so this call is available both 
through class and its instance.

### Removed
- **BREAKING CHANGES:** Because of previous point, all constants `CLASS_NAME` from `Task` classes are removed 
as they are not needed anymore.

## [v1.3.2](https://github.com/cleverreach/logeecore/compare/v1.3.1...v1.3.2) - 2018-04-10
### Changed
- Refactoring method for deactivating recipient

## [v1.3.1](https://github.com/cleverreach/logeecore/compare/v1.3.0...v1.3.1) - 2018-04-10
### Changed
- Fixed activated attribute for RecipientDeactivateSyncTask

## [v1.3.0](https://github.com/cleverreach/logeecore/compare/v1.2.0...v1.3.0) - 2018-03-23
**BREAKING CHANGES**:
- Removed methods `QueueItem::getProgress`, `QueueItem::setProgress`,
`QueueItem::getLastExecutionProgress` and `QueueItem::setLastExecutionProgress` and instead introduced new methods based
on [base points] `QueueItem::getProgressBasePoints`, `QueueItem::setProgressBasePoints`,
`QueueItem::getLastExecutionProgressBasePoints`, `QueueItem::setLastExecutionProgressBasePoints`. Additionally there is
new `QueueItem::getProgressFormatted` method that now returns float value of progress rounded to 2 decimals.
- Task **MUST** report progress in base points now, method `Task::reportProgress` accepts base points now instead of
previous percents.

All integrations using this version **MUST**:
- Update `TaskQueueStorage` interface implementation to save base points instead of percents
- All usages of removed methods **MUST** be transferred to base points counterpart methods
- Task **MUST** report progress in base points

### Removed
- Method `QueueItem::getProgress`
- Method `QueueItem::setProgress`
- Method `QueueItem::getLastExecutionProgress`
- Method `QueueItem::setLastExecutionProgress`
### Changed
- Method `Task::reportProgress` now accepts base points instad of whole percents
### Added
- Method `QueueItem::getProgressBasePoints`
- Method `QueueItem::setProgressBasePoints`
- Method `QueueItem::getLastExecutionProgressBasePoints`
- Method `QueueItem::setLastExecutionProgressBasePoints`
- Method `QueueItem::getProgressFormatted`

## [v1.2.0](https://github.com/cleverreach/logeecore/compare/v1.1.0...v1.2.0) - 2018-03-16
**BREAKING CHANGES**: Added new property `QueueItem::$lastExecutionProgress`. All integrations using this
version **MUST** update `TaskQueueStorage` interface implementation to support proper save and fetching of new property.

### Changed
- Based on added `QueueItem::$lastExecutionProgress` and `QueueItem::$progress` properties, `TaskRunner` now requeue
expired task when that expired task has progressed since last execution. Task will be marked as failed due to extended
inactivity period only when there was no progress since last task execution.
- Task progress can be updated only if queue item has most recent updated timestamp and last execution progress
- Task progress and keep alive updates now fail if storage was unable to store changes. This is done to prevent zombie
processes when task runner detects inactive task based on max inactivity period configuration but actual task process is
not inactive.
- Recipient sync task never send deactivated timestamp. Inactive customers are sent to CleveReach with activated
field set to value 0. This will mark recipient as inactive in CleverReach, but it will enable reactivation when
activated field is updated to value greater then 0. On the other hand when recipients are deactivated directly by
CleverReach system plugin will not be able to reactivate it again.
 
## [v1.1.0](https://github.com/cleverreach/logeecore/compare/v1.0.3...v1.1.0) - 2018-03-09
**BREAKING CHANGES**: Merged changes related to middleware integrations. 

Updated interfaces:  
- `CleverReach\Infrastructure\Interfaces\Required\Configuration`
- `CleverReach\Infrastructure\Interfaces\Required\TaskQueueStorage`
- `CleverReach\BusinessLogic\Interfaces\Recipients`

Removed interfaces:
- `CleverReach\Infrastructure\Interfaces\Required\TaskRunnerStatusStorage`

Added service `CleverReach\Infrastructure\TaskExecution\TaskRunnerStatusStorage` implementation so 
specific implementation can be removed.

### Added
- **Breaking**: Added new method `recipientSyncCompleted(array $recipientIds)` to 
`Recipients` interface. All integrations using this version **MUST** implement this method now.
Body of a method can be empty if integration does not need special handling when recipient 
is synced.

### Changed
- Based on added method, `RecipientSyncTask` now calls `Recipients` service when batch is synced
to notify batch complete.

## [v1.0.3](https://github.com/cleverreach/logeecore/compare/v1.0.2...v1.0.3) - 2018-03-06
### Changed
- Task runner instance is deactivated only after sleeping for wakeup delay interval
- Fixed bug in `FilterSyncTask`

## [v1.0.2](https://github.com/cleverreach/logeecore/compare/v1.0.1...v1.0.2) - 2018-03-05
### Changed
- Fixed progress reporting for `RecipientSyncTask`
- Fix bug with order purchase date

## [v1.0.1](https://github.com/cleverreach/logeecore/compare/v1.0.0...v1.0.1) - 2018-02-22
### Added
- Added new `RecipientDeactivateSyncTask` that will deactivate recipient by provided email

### Changed
- Refactored `RecipientDeactivateNewsletterStatusSyncTask` to be inline with added task. 
Now both have the same parent class so code is minimized.
- Updated all tests to be mutually independent

## [v1.0.0](https://github.com/cleverreach/logeecore/tree/v1.0.0) - 2018-02-03
- First release of CORE (_changelog missing because it wasn't maintained_)


[base points]: https://en.wikipedia.org/wiki/Basis_point