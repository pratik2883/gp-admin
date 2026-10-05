abstract interface class LeadListRefreshable {
  Future<void> refresh({String? q, String? status});

  Future<void> loadMore();
}
