class DiagnosticLocationsResponse {
  final List<Map<String, dynamic>> locations;
  final int? defaultLocationId;

  const DiagnosticLocationsResponse({
    required this.locations,
    required this.defaultLocationId,
  });
}

