<?php

namespace Google\AdsApi\AdWords\v201708\cm;


/**
 * This file was generated from WSDL. DO NOT EDIT.
 */
class BatchJobOpsService extends \Google\AdsApi\Common\AdsSoapClient
{

    /**
     * @var array $classmap The defined classes
     */
    private static $classmap = array (
      'Ad' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Ad',
      'AdCustomizerError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdCustomizerError',
      'AdError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdError',
      'AdGroup' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroup',
      'AdGroupAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupAd',
      'AdGroupAdCountLimitExceeded' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupAdCountLimitExceeded',
      'AdGroupAdError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupAdError',
      'AdGroupAdLabel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupAdLabel',
      'AdGroupAdLabelOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupAdLabelOperation',
      'AdGroupAdOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupAdOperation',
      'AdGroupAdPolicySummary' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupAdPolicySummary',
      'AdGroupBidModifier' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupBidModifier',
      'AdGroupBidModifierOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupBidModifierOperation',
      'AdGroupCriterion' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupCriterion',
      'AdGroupCriterionError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupCriterionError',
      'AdGroupCriterionLabel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupCriterionLabel',
      'AdGroupCriterionLabelOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupCriterionLabelOperation',
      'AdGroupCriterionLimitExceeded' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupCriterionLimitExceeded',
      'AdGroupCriterionOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupCriterionOperation',
      'AdGroupExtensionSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupExtensionSetting',
      'AdGroupExtensionSettingOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupExtensionSettingOperation',
      'AdGroupLabel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupLabel',
      'AdGroupLabelOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupLabelOperation',
      'AdGroupOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupOperation',
      'AdGroupServiceError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdGroupServiceError',
      'AdSchedule' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdSchedule',
      'AdSharingError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdSharingError',
      'AdUnionId' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdUnionId',
      'Address' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Address',
      'AdxError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AdxError',
      'AgeRange' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AgeRange',
      'ApiError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ApiError',
      'ApiException' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ApiException',
      'AppFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AppFeedItem',
      'AppPaymentModel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AppPaymentModel',
      'AppUrl' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AppUrl',
      'AppUrlList' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AppUrlList',
      'ApplicationException' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ApplicationException',
      'LabelAttribute' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\LabelAttribute',
      'Audio' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Audio',
      'AuthenticationError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AuthenticationError',
      'AuthorizationError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\AuthorizationError',
      'Bid' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Bid',
      'BiddableAdGroupCriterion' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\BiddableAdGroupCriterion',
      'BiddingErrors' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\BiddingErrors',
      'BiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\BiddingScheme',
      'BiddingStrategyConfiguration' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\BiddingStrategyConfiguration',
      'Bids' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Bids',
      'Budget' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Budget',
      'BudgetError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\BudgetError',
      'BudgetOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\BudgetOperation',
      'CallConversionType' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CallConversionType',
      'CallFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CallFeedItem',
      'CallOnlyAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CallOnlyAd',
      'CalloutFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CalloutFeedItem',
      'Campaign' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Campaign',
      'CampaignCriterion' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignCriterion',
      'CampaignCriterionError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignCriterionError',
      'CampaignCriterionOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignCriterionOperation',
      'CampaignError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignError',
      'CampaignExtensionSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignExtensionSetting',
      'CampaignExtensionSettingOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignExtensionSettingOperation',
      'CampaignLabel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignLabel',
      'CampaignLabelOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignLabelOperation',
      'TextLabel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TextLabel',
      'DisplayAttribute' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DisplayAttribute',
      'CampaignOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CampaignOperation',
      'Carrier' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Carrier',
      'ClientTermsError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ClientTermsError',
      'CollectionSizeError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CollectionSizeError',
      'ComparableValue' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ComparableValue',
      'ConstantOperand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ConstantOperand',
      'ContentLabel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ContentLabel',
      'ConversionOptimizerEligibility' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ConversionOptimizerEligibility',
      'CountryConstraint' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CountryConstraint',
      'CpaBid' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CpaBid',
      'CpcBid' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CpcBid',
      'CpmBid' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CpmBid',
      'Criterion' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Criterion',
      'CriterionError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CriterionError',
      'CriterionParameter' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CriterionParameter',
      'CriterionPolicyError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CriterionPolicyError',
      'CustomParameter' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CustomParameter',
      'CustomParameters' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CustomParameters',
      'CustomerExtensionSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CustomerExtensionSetting',
      'CustomerExtensionSettingOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CustomerExtensionSettingOperation',
      'DatabaseError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DatabaseError',
      'DateError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DateError',
      'DateRangeError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DateRangeError',
      'DeprecatedAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DeprecatedAd',
      'Dimensions' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Dimensions',
      'DisapprovalReason' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DisapprovalReason',
      'DistinctError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DistinctError',
      'DoubleValue' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DoubleValue',
      'DynamicSearchAdsSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DynamicSearchAdsSetting',
      'DynamicSettings' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DynamicSettings',
      'EnhancedCpcBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\EnhancedCpcBiddingScheme',
      'EntityAccessDenied' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\EntityAccessDenied',
      'EntityCountLimitExceeded' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\EntityCountLimitExceeded',
      'EntityNotFound' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\EntityNotFound',
      'ErrorList' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ErrorList',
      'ExemptionRequest' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ExemptionRequest',
      'ExpandedDynamicSearchAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ExpandedDynamicSearchAd',
      'ExpandedTextAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ExpandedTextAd',
      'ExplorerAutoOptimizerSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ExplorerAutoOptimizerSetting',
      'ExtensionFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ExtensionFeedItem',
      'ExtensionSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ExtensionSetting',
      'ExtensionSettingError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ExtensionSettingError',
      'FeedAttributeReferenceError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedAttributeReferenceError',
      'FeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItem',
      'FeedItemAdGroupTargeting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemAdGroupTargeting',
      'FeedItemAttributeError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemAttributeError',
      'FeedItemAttributeValue' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemAttributeValue',
      'FeedItemCampaignTargeting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemCampaignTargeting',
      'FeedItemDevicePreference' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemDevicePreference',
      'FeedItemError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemError',
      'FeedItemGeoRestriction' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemGeoRestriction',
      'FeedItemOperation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemOperation',
      'FeedItemPolicyData' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemPolicyData',
      'FeedItemSchedule' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemSchedule',
      'FeedItemScheduling' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FeedItemScheduling',
      'FieldPathElement' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FieldPathElement',
      'ForwardCompatibilityError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ForwardCompatibilityError',
      'FrequencyCap' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FrequencyCap',
      'Function' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MatchingFunction',
      'FunctionError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FunctionError',
      'FunctionParsingError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FunctionParsingError',
      'Gender' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Gender',
      'GeoPoint' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\GeoPoint',
      'GeoTargetOperand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\GeoTargetOperand',
      'GeoTargetTypeSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\GeoTargetTypeSetting',
      'IdError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\IdError',
      'Image' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Image',
      'ImageAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ImageAd',
      'ImageError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ImageError',
      'IncomeOperand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\IncomeOperand',
      'IncomeRange' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\IncomeRange',
      'InternalApiError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\InternalApiError',
      'IpBlock' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\IpBlock',
      'Keyword' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Keyword',
      'Label' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Label',
      'Language' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Language',
      'ListError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ListError',
      'ListOperations' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ListOperations',
      'Location' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Location',
      'LocationExtensionOperand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\LocationExtensionOperand',
      'LongValue' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\LongValue',
      'ManualCpcBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ManualCpcBiddingScheme',
      'ManualCpmBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ManualCpmBiddingScheme',
      'MaximizeConversionsBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MaximizeConversionsBiddingScheme',
      'Media' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Media',
      'MediaBundle' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MediaBundle',
      'MediaBundleError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MediaBundleError',
      'MediaError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MediaError',
      'Media_Size_DimensionsMapEntry' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Media_Size_DimensionsMapEntry',
      'Media_Size_StringMapEntry' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Media_Size_StringMapEntry',
      'MessageFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MessageFeedItem',
      'MobileAppCategory' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MobileAppCategory',
      'MobileApplication' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MobileApplication',
      'MobileDevice' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MobileDevice',
      'Money' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Money',
      'MoneyWithCurrency' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MoneyWithCurrency',
      'UniversalAppCampaignSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\UniversalAppCampaignSetting',
      'MultiplierError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MultiplierError',
      'MutateResult' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\MutateResult',
      'NegativeAdGroupCriterion' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\NegativeAdGroupCriterion',
      'NegativeCampaignCriterion' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\NegativeCampaignCriterion',
      'NetworkSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\NetworkSetting',
      'NewEntityCreationError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\NewEntityCreationError',
      'NotEmptyError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\NotEmptyError',
      'NullError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\NullError',
      'NumberValue' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\NumberValue',
      'FunctionArgumentOperand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\FunctionArgumentOperand',
      'Operand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Operand',
      'OperatingSystemVersion' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\OperatingSystemVersion',
      'Operation' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Operation',
      'OperationAccessDenied' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\OperationAccessDenied',
      'OperatorError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\OperatorError',
      'PageFeed' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PageFeed',
      'PageOnePromotedBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PageOnePromotedBiddingScheme',
      'PagingError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PagingError',
      'Parent' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ParentCriterion',
      'Placement' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Placement',
      'PlacesOfInterestOperand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PlacesOfInterestOperand',
      'Platform' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Platform',
      'PolicyData' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PolicyData',
      'PolicyTopicConstraint' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PolicyTopicConstraint',
      'PolicyTopicEntry' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PolicyTopicEntry',
      'PolicyTopicEvidence' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PolicyTopicEvidence',
      'PolicyViolationError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PolicyViolationError',
      'PolicyViolationError.Part' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PolicyViolationErrorPart',
      'PolicyViolationKey' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PolicyViolationKey',
      'PriceFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PriceFeedItem',
      'PriceTableRow' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PriceTableRow',
      'ProductAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductAd',
      'ProductAdwordsGrouping' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductAdwordsGrouping',
      'ProductAdwordsLabels' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductAdwordsLabels',
      'ProductBiddingCategory' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductBiddingCategory',
      'ProductBrand' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductBrand',
      'ProductCanonicalCondition' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductCanonicalCondition',
      'ProductChannel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductChannel',
      'ProductChannelExclusivity' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductChannelExclusivity',
      'ProductLegacyCondition' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductLegacyCondition',
      'ProductCustomAttribute' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductCustomAttribute',
      'ProductDimension' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductDimension',
      'ProductOfferId' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductOfferId',
      'ProductPartition' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductPartition',
      'ProductScope' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductScope',
      'ProductType' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductType',
      'ProductTypeFull' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ProductTypeFull',
      'PromotionFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\PromotionFeedItem',
      'Proximity' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Proximity',
      'QualityInfo' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\QualityInfo',
      'QueryError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\QueryError',
      'QuotaCheckError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\QuotaCheckError',
      'RangeError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RangeError',
      'RateExceededError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RateExceededError',
      'ReadOnlyError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ReadOnlyError',
      'RealTimeBiddingSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RealTimeBiddingSetting',
      'RegionCodeError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RegionCodeError',
      'RejectedError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RejectedError',
      'RequestError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RequestError',
      'RequiredError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RequiredError',
      'ResponsiveDisplayAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ResponsiveDisplayAd',
      'ReviewFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ReviewFeedItem',
      'RichMediaAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\RichMediaAd',
      'SelectiveOptimization' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\SelectiveOptimization',
      'SelectorError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\SelectorError',
      'LocationGroups' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\LocationGroups',
      'Setting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Setting',
      'SettingError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\SettingError',
      'ShoppingSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ShoppingSetting',
      'ShowcaseAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ShowcaseAd',
      'SitelinkFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\SitelinkFeedItem',
      'SizeLimitError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\SizeLimitError',
      'StatsQueryError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\StatsQueryError',
      'StringFormatError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\StringFormatError',
      'StringLengthError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\StringLengthError',
      'String_StringMapEntry' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\String_StringMapEntry',
      'StructuredSnippetFeedItem' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\StructuredSnippetFeedItem',
      'TargetCpaBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TargetCpaBiddingScheme',
      'TargetOutrankShareBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TargetOutrankShareBiddingScheme',
      'TargetingSettingDetail' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TargetingSettingDetail',
      'TargetRoasBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TargetRoasBiddingScheme',
      'TargetSpendBiddingScheme' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TargetSpendBiddingScheme',
      'TargetingSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TargetingSetting',
      'TempAdUnionId' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TempAdUnionId',
      'TemplateAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TemplateAd',
      'TemplateElement' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TemplateElement',
      'TemplateElementField' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TemplateElementField',
      'TextAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TextAd',
      'ThirdPartyRedirectAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\ThirdPartyRedirectAd',
      'TrackingSetting' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\TrackingSetting',
      'UniversalAppCampaignAdsPolicyDecisions' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\UniversalAppCampaignAdsPolicyDecisions',
      'UnknownProductDimension' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\UnknownProductDimension',
      'UrlData' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\UrlData',
      'UrlError' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\UrlError',
      'UrlList' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\UrlList',
      'CriterionUserInterest' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CriterionUserInterest',
      'CriterionUserList' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\CriterionUserList',
      'VanityPharma' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\VanityPharma',
      'Vertical' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Vertical',
      'Video' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Video',
      'Webpage' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\Webpage',
      'WebpageCondition' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\WebpageCondition',
      'WebpageParameter' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\WebpageParameter',
      'DynamicSearchAd' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\DynamicSearchAd',
      'YouTubeChannel' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\YouTubeChannel',
      'YouTubeVideo' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\YouTubeVideo',
      'mutateResponse' => 'Google\\AdsApi\\AdWords\\v201708\\cm\\mutateResponse',
    );

    /**
     * @param array $options A array of config values
     * @param string $wsdl The wsdl file to use
     */
    public function __construct(array $options = array(),
                $wsdl = 'https://adwords.google.com/api/adwords/cm/v201708/BatchJobOpsService?wsdl')
    {
      foreach (self::$classmap as $key => $value) {
        if (!isset($options['classmap'][$key])) {
          $options['classmap'][$key] = $value;
        }
      }
      $options = array_merge(array (
      'features' => 1,
    ), $options);
      parent::__construct($wsdl, $options);
    }

    /**
     * @param \Google\AdsApi\AdWords\v201708\cm\Operation[] $operations
     * @return \Google\AdsApi\AdWords\v201708\cm\MutateResult[]
     * @throws \Google\AdsApi\AdWords\v201708\cm\ApiException
     */
    public function mutate(array $operations)
    {
      return $this->__soapCall('mutate', array(array('operations' => $operations)))->getRval();
    }

}
