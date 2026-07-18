import 'dart:async';

import 'package:flutter/material.dart';
import 'package:flutter_google_places_sdk/flutter_google_places_sdk.dart';
import 'package:gp_app/ui/styles.dart';

class GoogleAddressSelection {
  const GoogleAddressSelection({
    required this.fullAddress,
    required this.street,
    required this.area,
    required this.city,
    required this.pincode,
    this.placeId,
    this.latitude,
    this.longitude,
  });

  final String fullAddress;
  final String street;
  final String area;
  final String city;
  final String pincode;
  final String? placeId;
  final double? latitude;
  final double? longitude;

  String get cityArea {
    if (area.isNotEmpty && city.isNotEmpty) {
      return '$city, $area';
    }
    return city.isNotEmpty ? city : area;
  }

  factory GoogleAddressSelection.fromPlace({
    required AutocompletePrediction prediction,
    required Place? place,
  }) {
    final address = (place?.address ?? prediction.fullText).trim();
    final street = prediction.primaryText.trim();
    final secondaryParts = prediction.secondaryText
        .split(',')
        .map((part) => part.trim())
        .where((part) => part.isNotEmpty)
        .toList();

    var area = secondaryParts.isNotEmpty ? secondaryParts.first : '';
    var city = secondaryParts.length >= 2 ? secondaryParts[1] : '';

    if (city.isEmpty && secondaryParts.length == 1) {
      city = secondaryParts.first;
      area = '';
    }

    if (city.isEmpty) {
      final fullParts = address
          .split(',')
          .map((part) => part.trim())
          .where((part) => part.isNotEmpty)
          .toList();
      if (fullParts.length >= 3) {
        area = area.isEmpty ? fullParts[1] : area;
        city = fullParts[2];
      } else if (fullParts.length >= 2) {
        city = fullParts[1];
      }
    }

    final pincodeMatch = RegExp(r'\b\d{5,6}\b').firstMatch(address);
    final pincode = pincodeMatch?.group(0) ?? '';
    city = city.replaceAll(RegExp(r'\b\d{5,6}\b'), '').trim();

    return GoogleAddressSelection(
      fullAddress: address,
      street: street,
      area: area,
      city: city,
      pincode: pincode,
      placeId: place?.id ?? prediction.placeId,
      latitude: place?.latLng?.lat,
      longitude: place?.latLng?.lng,
    );
  }
}

class GoogleAddressAutocompleteField extends StatefulWidget {
  const GoogleAddressAutocompleteField({
    super.key,
    required this.apiKey,
    required this.countryCode,
    required this.onSelected,
    this.label = 'Search Address',
    this.hintText = 'Search with Google',
    this.initialValue,
    this.currentLatitude,
    this.currentLongitude,
  });

  final String apiKey;
  final String countryCode;
  final ValueChanged<GoogleAddressSelection> onSelected;
  final String label;
  final String hintText;
  final String? initialValue;
  final double? currentLatitude;
  final double? currentLongitude;

  @override
  State<GoogleAddressAutocompleteField> createState() => _GoogleAddressAutocompleteFieldState();
}

class _GoogleAddressAutocompleteFieldState extends State<GoogleAddressAutocompleteField> {
  late final TextEditingController _controller;
  late final FlutterGooglePlacesSdk _places;
  final FocusNode _focusNode = FocusNode();
  Timer? _debounce;
  Timer? _timeout;
  List<AutocompletePrediction> _predictions = const [];
  bool _loading = false;
  String? _error;

  @override
  void initState() {
    super.initState();
    _controller = TextEditingController(text: widget.initialValue ?? '');
    try {
      _places = FlutterGooglePlacesSdk(widget.apiKey);
    } catch (e) {
      WidgetsBinding.instance.addPostFrameCallback((_) {
        if (mounted) {
          setState(() => _error = 'Google Places SDK init failed');
        }
      });
      _places = FlutterGooglePlacesSdk('');
    }
    _focusNode.addListener(() {
      if (!_focusNode.hasFocus && mounted) {
        Future<void>.delayed(const Duration(milliseconds: 150), () {
          if (mounted && !_focusNode.hasFocus) {
            setState(() => _predictions = const []);
          }
        });
      }
    });
  }

  @override
  void dispose() {
    _debounce?.cancel();
    _timeout?.cancel();
    _controller.dispose();
    _focusNode.dispose();
    super.dispose();
  }

  void _onChanged(String value) {
    _debounce?.cancel();
    _timeout?.cancel();
    final query = value.trim();
    if (query.length < 3) {
      setState(() {
        _predictions = const [];
        _loading = false;
        _error = null;
      });
      return;
    }

    _debounce = Timer(const Duration(milliseconds: 350), () async {
      setState(() {
        _loading = true;
        _error = null;
      });

      _timeout = Timer(const Duration(seconds: 10), () {
        if (mounted) {
          setState(() {
            _loading = false;
            _error = 'Request timed out. Check network & API key setup.';
          });
        }
      });

      try {
        final response = await _places.findAutocompletePredictions(
          query,
          countries: [widget.countryCode.toUpperCase()],
          origin: widget.currentLatitude != null && widget.currentLongitude != null
              ? LatLng(lat: widget.currentLatitude!, lng: widget.currentLongitude!)
              : null,
        );
        _timeout?.cancel();
        if (!mounted) return;
        setState(() {
          _predictions = response.predictions;
          _loading = false;
        });
      } catch (e) {
        _timeout?.cancel();
        if (!mounted) return;
        final msg = e.toString();
        String friendly;
        if (msg.contains('ACCESS_NOT_CONFIGURED') || msg.contains('NOT_CONFIGURED')) {
          friendly = 'Places API not enabled in Google Cloud Console';
        } else if (msg.contains('REQUEST_DENIED') || msg.contains('not authorized') || msg.contains('API key')) {
          friendly = 'API key not authorized (SHA-1 mismatch?)';
        } else if (msg.contains('INVALID_ARGUMENT')) {
          friendly = 'Invalid request';
        } else {
          friendly = 'Unable to load address suggestions';
        }
        setState(() {
          _loading = false;
          _error = friendly;
        });
      }
    });
  }

  Future<void> _selectPrediction(AutocompletePrediction prediction) async {
    setState(() {
      _loading = true;
      _error = null;
    });

    _timeout = Timer(const Duration(seconds: 10), () {
      if (mounted) {
        setState(() {
          _loading = false;
          _error = 'Fetching place details timed out';
        });
      }
    });

    try {
      final response = await _places.fetchPlace(
        prediction.placeId,
        fields: const [
          PlaceField.Id,
          PlaceField.Address,
          PlaceField.Location,
          PlaceField.Name,
        ],
      );
      _timeout?.cancel();
      final selection = GoogleAddressSelection.fromPlace(
        prediction: prediction,
        place: response.place,
      );
      if (!mounted) return;
      _controller.text = selection.fullAddress;
      _controller.selection = TextSelection.collapsed(offset: _controller.text.length);
      setState(() {
        _predictions = const [];
        _loading = false;
      });
      widget.onSelected(selection);
    } catch (_) {
      _timeout?.cancel();
      if (!mounted) return;
      setState(() {
        _loading = false;
        _error = 'Unable to fetch address details';
      });
    }
  }

  @override
  Widget build(BuildContext context) {
    return Column(
      crossAxisAlignment: CrossAxisAlignment.start,
      children: [
        TextFormField(
          controller: _controller,
          focusNode: _focusNode,
          onChanged: _onChanged,
          style: AppStyles.bodyMedium.copyWith(color: AppColors.textPrimary),
          decoration: InputDecoration(
            labelText: widget.label,
            hintText: widget.hintText,
            prefixIcon: const Icon(Icons.location_on_outlined),
            suffixIcon: _loading
                ? const Padding(
                    padding: EdgeInsets.all(12),
                    child: SizedBox(
                      width: 18,
                      height: 18,
                      child: CircularProgressIndicator(strokeWidth: 2),
                    ),
                  )
                : (_controller.text.trim().isEmpty
                    ? null
                    : IconButton(
                        onPressed: () {
                          _controller.clear();
                          setState(() {
                            _predictions = const [];
                            _error = null;
                          });
                        },
                        icon: const Icon(Icons.close_rounded),
                      )),
          ),
        ),
        if (_error != null) ...[
          const SizedBox(height: 8),
          Container(
            padding: const EdgeInsets.symmetric(horizontal: 12, vertical: 8),
            decoration: BoxDecoration(
              color: Colors.red.shade50,
              borderRadius: BorderRadius.circular(8),
              border: Border.all(color: Colors.red.shade200),
            ),
            child: Row(
              children: [
                Icon(Icons.info_outline, size: 16, color: Colors.red.shade700),
                const SizedBox(width: 8),
                Expanded(
                  child: Text(
                    _error!,
                    style: AppStyles.caption.copyWith(color: Colors.red.shade700),
                  ),
                ),
              ],
            ),
          ),
        ],
        if (_predictions.isNotEmpty) ...[
          const SizedBox(height: 8),
          Container(
            decoration: BoxDecoration(
              color: Colors.white,
              borderRadius: AppStyles.radiusInput,
              border: Border.all(color: AppColors.borderLight),
              boxShadow: AppStyles.cardShadow,
            ),
            child: ListView.separated(
              shrinkWrap: true,
              physics: const NeverScrollableScrollPhysics(),
              itemCount: _predictions.length.clamp(0, 5),
              separatorBuilder: (_, __) => const Divider(height: 1),
              itemBuilder: (context, index) {
                final item = _predictions[index];
                return ListTile(
                  dense: true,
                  leading: const Icon(Icons.place_outlined, color: AppColors.primaryBlue),
                  title: Text(
                    item.primaryText.isNotEmpty ? item.primaryText : item.fullText,
                    style: AppStyles.bodyMedium.copyWith(fontWeight: FontWeight.w600),
                  ),
                  subtitle: item.secondaryText.isEmpty ? null : Text(item.secondaryText),
                  onTap: () => _selectPrediction(item),
                );
              },
            ),
          ),
        ],
      ],
    );
  }
}
